<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentWebhook;
use App\Services\BillingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function secret(): string
    {
        return config('integrations.payments.providers.mock.secret');
    }

    protected function webhookRequest(Payment $payment, string $outcome = 'completed', ?string $eventId = null, ?int $amountOverride = null): array
    {
        $body = json_encode([
            'event_id' => $eventId ?? (string) Str::uuid(),
            'event_type' => 'payment.'.$outcome,
            'payment_uuid' => $payment->uuid,
            'provider_reference' => $payment->provider_reference,
            'amount_cents' => $amountOverride ?? $payment->amount_cents,
            'currency' => 'USD',
            'paid_at' => now()->toIso8601String(),
        ]);

        return [
            'body' => $body,
            'signature' => hash_hmac('sha256', $body, $this->secret()),
        ];
    }

    protected function initiatePayment($amountCents = 5000): array
    {
        $user = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $this->link($user, $account->property);
        $this->makeBill($account, $amountCents);

        $result = app(PaymentService::class)->initiate($user, $account, $amountCents, 'mock', 'idem-'.Str::uuid());

        return [$user, $account, $result['payment']];
    }

    public function test_initiate_creates_pending_payment_with_provider_reference(): void
    {
        [$user, $account, $payment] = $this->initiatePayment();

        $this->assertEquals('pending', $payment->status);
        $this->assertNotNull($payment->provider_reference);
        $this->assertEquals($account->id, $payment->municipal_account_id);
    }

    public function test_signed_webhook_marks_payment_successful_and_allocates(): void
    {
        [$user, $account, $payment] = $this->initiatePayment(5000);
        ['body' => $body, 'signature' => $sig] = $this->webhookRequest($payment);

        $this->postJson('/webhooks/payments/mock', json_decode($body, true), ['X-Provider-Signature' => $sig])
            ->assertOk();

        $payment->refresh();
        $this->assertEquals('successful', $payment->status);
        $this->assertEquals(0, $payment->unallocatedCents());
        $this->assertEquals('paid', $payment->account->bills->first()->fresh()->status);
        $this->assertEquals(0, $payment->account->fresh()->balance_cents);
    }

    public function test_webhook_with_bad_signature_is_rejected(): void
    {
        [, , $payment] = $this->initiatePayment();
        ['body' => $body] = $this->webhookRequest($payment);

        $this->postJson('/webhooks/payments/mock', json_decode($body, true), ['X-Provider-Signature' => 'invalid'])
            ->assertForbidden();

        $this->assertEquals('pending', $payment->fresh()->status);
    }

    public function test_duplicate_webhook_is_ignored(): void
    {
        [$user, $account, $payment] = $this->initiatePayment(5000);
        $eventId = (string) Str::uuid();
        ['body' => $body, 'signature' => $sig] = $this->webhookRequest($payment, 'completed', $eventId);

        $this->postJson('/webhooks/payments/mock', json_decode($body, true), ['X-Provider-Signature' => $sig])->assertOk();
        // replay same event
        $this->postJson('/webhooks/payments/mock', json_decode($body, true), ['X-Provider-Signature' => $sig])->assertOk();

        $this->assertEquals(1, PaymentWebhook::where('event_id', $eventId)->count());
        $this->assertEquals(1, $payment->fresh()->allocations()->count());
    }

    public function test_amount_mismatch_flags_webhook_without_finalizing(): void
    {
        [, , $payment] = $this->initiatePayment(5000);
        ['body' => $body, 'signature' => $sig] = $this->webhookRequest($payment, 'completed', null, 9999);

        $this->postJson('/webhooks/payments/mock', json_decode($body, true), ['X-Provider-Signature' => $sig]);

        $this->assertEquals('pending', $payment->fresh()->status);
        $this->assertEquals('failed', PaymentWebhook::latest()->first()->status);
    }

    public function test_failed_webhook_marks_payment_failed(): void
    {
        [, , $payment] = $this->initiatePayment();
        ['body' => $body, 'signature' => $sig] = $this->webhookRequest($payment, 'failed');

        $this->postJson('/webhooks/payments/mock', json_decode($body, true), ['X-Provider-Signature' => $sig])->assertOk();
        $this->assertEquals('failed', $payment->fresh()->status);
    }

    public function test_idempotent_initiation_returns_same_payment(): void
    {
        $user = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $this->link($user, $account->property);

        $key = (string) Str::uuid();
        $a = app(PaymentService::class)->initiate($user, $account, 5000, 'mock', $key)['payment'];
        $b = app(PaymentService::class)->initiate($user, $account, 5000, 'mock', $key)['payment'];

        $this->assertEquals($a->id, $b->id);
        $this->assertEquals(1, Payment::where('idempotency_key', $key)->count());
    }

    public function test_overpayment_leaves_credit_on_account(): void
    {
        [$user, $account, $payment] = $this->initiatePayment(10000); // bill is 10000

        // pay more than the bill
        $over = app(PaymentService::class)->initiate($user, $account, 15000, 'mock', 'over-'.Str::uuid())['payment'];
        ['body' => $body, 'signature' => $sig] = $this->webhookRequest($over);
        $this->postJson('/webhooks/payments/mock', json_decode($body, true), ['X-Provider-Signature' => $sig]);

        $this->assertEquals(-5000, $account->fresh()->balance_cents); // credit
    }

    public function test_partial_payment_marks_bill_partially_paid(): void
    {
        [$user, $account] = [$this->makeUser(), $this->makePropertyWithAccount()];
        $this->link($user, $account->property);
        $bill = $this->makeBill($account, 10000);

        $payment = app(PaymentService::class)->initiate($user, $account, 4000, 'mock')['payment'];
        ['body' => $body, 'signature' => $sig] = $this->webhookRequest($payment);
        $this->postJson('/webhooks/payments/mock', json_decode($body, true), ['X-Provider-Signature' => $sig]);

        $bill->refresh();
        $this->assertEquals('partially_paid', $bill->status);
        $this->assertEquals(6000, $bill->outstandingCents());
    }

    public function test_webhook_for_unknown_payment_is_recorded_but_ignored(): void
    {
        $body = json_encode([
            'event_id' => (string) Str::uuid(), 'event_type' => 'payment.completed',
            'payment_uuid' => (string) Str::uuid(), 'provider_reference' => 'NOPE',
            'amount_cents' => 100, 'currency' => 'USD',
        ]);
        $sig = hash_hmac('sha256', $body, $this->secret());

        $this->postJson('/webhooks/payments/mock', json_decode($body, true), ['X-Provider-Signature' => $sig]);
        $this->assertEquals('failed', PaymentWebhook::latest()->first()->status);
    }

    protected function makeBill($account, int $amount = 5000): Bill
    {
        return app(BillingService::class)->issueBill($account, [
            'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth(),
            'due_date' => now()->addDays(14),
        ], [['charge_type' => 'rates', 'description' => 'Rates', 'quantity' => 1, 'unit_price_cents' => $amount]]);
    }
}
