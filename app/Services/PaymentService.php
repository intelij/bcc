<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentWebhook;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Payment state machine + webhook processing. The ONLY code allowed to move
 * a payment to `successful` lives here — and it requires either a verified
 * provider callback or an authorized staff action.
 */
class PaymentService
{
    public function __construct(
        protected PaymentManager $providers,
        protected NotificationService $notifications,
        protected BillingService $billing,
        protected AuditService $audit,
    ) {}

    /**
     * Begin a payment. Idempotent: the same idempotency_key returns the same
     * payment instead of creating a duplicate.
     *
     * @return array{payment: Payment, initiation: array}
     */
    public function initiate(User $user, MunicipalAccount $account, int $amountCents, string $providerKey, ?string $idempotencyKey = null): array
    {
        if ($amountCents <= 0) {
            throw new BadRequestHttpException('Amount must be greater than zero.');
        }
        if ($account->status !== MunicipalAccount::STATUS_ACTIVE) {
            throw new AccessDeniedHttpException('This account is not payable.');
        }

        $idempotencyKey ??= (string) Str::uuid();

        // Idempotent replay — return the existing attempt untouched.
        if ($existing = Payment::where('idempotency_key', $idempotencyKey)->first()) {
            return ['payment' => $existing, 'initiation' => $existing->meta['initiation'] ?? [], 'reused' => true];
        }

        $provider = $this->providers->driver($providerKey);

        return DB::transaction(function () use ($user, $account, $amountCents, $provider, $providerKey, $idempotencyKey) {
            $payment = Payment::create([
                'municipal_account_id' => $account->id,
                'property_id' => $account->property_id,
                'user_id' => $user->id,
                'amount_cents' => $amountCents,
                'currency' => config('city.currency', 'USD'),
                'provider' => $providerKey,
                'payment_method' => $providerKey === 'bank_transfer' ? 'bank_transfer' : 'mobile_money',
                'reference' => $account->account_number,
                'idempotency_key' => $idempotencyKey,
                'status' => Payment::STATUS_PENDING,
                'source' => 'portal',
            ]);

            $initiation = $provider->initiate($payment);
            $payment->forceFill(['meta' => ['initiation' => $initiation]])->save();

            $this->audit->record('payment.initiated', $payment, null, [
                'amount_cents' => $amountCents, 'provider' => $providerKey,
            ]);

            return ['payment' => $payment, 'initiation' => $initiation, 'reused' => false];
        });
    }

    /**
     * Process a provider webhook: verify → dedupe → parse → transition.
     * Returns the stored webhook record.
     */
    public function handleWebhook(string $providerKey, Request $request): PaymentWebhook
    {
        $provider = $this->providers->driver($providerKey);

        if (! $provider->verifyWebhook($request)) {
            $hook = PaymentWebhook::create([
                'provider' => $providerKey, 'signature' => $request->header('X-Provider-Signature'),
                'payload' => json_decode($request->getContent(), true) ?: $request->all(),
                'status' => 'invalid', 'error' => 'signature verification failed',
            ]);
            $this->audit->record('payment.webhook_invalid', $hook);
            abort(403, 'Invalid webhook signature.');
        }

        $parsed = $provider->parseWebhook($request);
        $eventId = $parsed['event_id'] ?? null;

        // Dedupe: provider+event_id unique constraint enforces replay safety.
        if ($eventId && ($existing = PaymentWebhook::where('provider', $providerKey)->where('event_id', $eventId)->first())) {
            $existing->forceFill(['status' => $existing->status === 'processed' ? 'ignored' : $existing->status])->save();

            return $existing->fresh();
        }

        $hook = PaymentWebhook::create([
            'provider' => $providerKey,
            'event_id' => $eventId,
            'event_type' => $parsed['event_type'] ?? 'unknown',
            'signature' => $request->header('X-Provider-Signature'),
            'payload' => json_decode($request->getContent(), true) ?: $request->all(),
            'status' => 'received',
        ]);

        if (! $parsed) {
            $hook->forceFill(['status' => 'ignored', 'error' => 'unrecognized payload'])->save();

            return $hook;
        }

        $payment = Payment::where('provider_reference', $parsed['provider_reference'] ?? '__none__')
            ->orWhere('uuid', $parsed['payment_uuid'] ?? '__none__')
            ->first();

        if (! $payment) {
            $hook->forceFill(['status' => 'failed', 'error' => 'payment not found'])->save();

            return $hook;
        }

        $hook->forceFill(['payment_id' => $payment->id])->save();

        // Amount mismatch → flag for reconciliation, never auto-finalize.
        if (isset($parsed['amount_cents']) && (int) $parsed['amount_cents'] !== (int) $payment->amount_cents) {
            $hook->forceFill(['status' => 'failed', 'error' => 'amount mismatch'])->save();
            $this->audit->record('payment.webhook_amount_mismatch', $payment,
                ['amount_cents' => $payment->amount_cents], $parsed);

            return $hook;
        }

        match ($parsed['status']) {
            'successful' => $this->markSuccessful($payment, $parsed['provider_reference'] ?? null, $parsed['paid_at'] ?? null),
            'failed' => $this->transition($payment, Payment::STATUS_FAILED, $parsed['failure_reason'] ?? 'provider reported failure'),
            'cancelled' => $this->transition($payment, Payment::STATUS_CANCELLED, 'cancelled by payer'),
            default => $this->transition($payment, Payment::STATUS_PROCESSING),
        };

        $hook->forceFill(['status' => 'processed', 'processed_at' => now()])->save();

        return $hook;
    }

    /**
     * Finalize a payment — verified callback or authorized staff action only.
     * Allocates across the account's open bills FIFO and recomputes balances.
     */
    public function markSuccessful(Payment $payment, ?string $providerReference = null, ?string $paidAt = null, ?User $actor = null): Payment
    {
        return DB::transaction(function () use ($payment, $providerReference, $paidAt) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status === Payment::STATUS_SUCCESSFUL) {
                return $payment; // idempotent
            }
            if (! in_array($payment->status, Payment::OPEN_STATUSES) && $payment->status !== Payment::STATUS_UNALLOCATED) {
                return $payment; // already final — ignore late/duplicate webhooks
            }

            $old = $payment->only('status', 'provider_reference', 'reconciliation_status');
            $payment->forceFill([
                'status' => Payment::STATUS_SUCCESSFUL,
                'provider_reference' => $providerReference ?? $payment->provider_reference,
                'paid_at' => $paidAt ? now()->parse($paidAt) : now(),
                'reconciliation_status' => 'reconciled',
            ])->save();

            $this->allocateToBills($payment);
            $this->billing->recomputeAccount($payment->account);

            $this->audit->record('payment.successful', $payment, $old, $payment->only('status', 'provider_reference', 'reconciliation_status'));

            if ($payment->user) {
                $this->notifications->notifyUser($payment->user, 'PAYMENT_RECEIVED', [
                    'title' => 'Payment received',
                    'message' => 'Payment of '.money($payment->amount_cents).' was received for account '.$payment->account->account_number.'.',
                    'data' => ['payment_uuid' => $payment->uuid],
                ]);
            }

            return $payment->fresh();
        });
    }

    public function transition(Payment $payment, string $to, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($payment, $to, $reason) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->isFinal()) {
                return $payment;
            }
            $old = $payment->only('status');
            $payment->forceFill(['status' => $to, 'failure_reason' => $reason])->save();
            $this->audit->record('payment.'.$to, $payment, $old, ['status' => $to]);

            if ($payment->user && in_array($to, [Payment::STATUS_FAILED, Payment::STATUS_CANCELLED])) {
                $this->notifications->notifyUser($payment->user, 'PAYMENT_FAILED', [
                    'title' => 'Payment '.strtolower($to),
                    'message' => 'Your payment of '.money($payment->amount_cents).' could not be completed'.($reason ? ": $reason" : '.'),
                    'data' => ['payment_uuid' => $payment->uuid],
                ]);
            }

            return $payment->fresh();
        });
    }

    /** Staff action: approve a manual/bank payment after proof review. */
    public function approveManual(Payment $payment, User $staff): Payment
    {
        return $this->markSuccessful($payment, $payment->provider_reference, null, $staff);
    }

    public function rejectManual(Payment $payment, User $staff, string $reason): Payment
    {
        return $this->transition($payment, Payment::STATUS_FAILED, "Rejected by finance: $reason");
    }

    /** Allocate a successful payment across open bills, oldest first. */
    public function allocateToBills(Payment $payment, ?User $actor = null): int
    {
        $remaining = $payment->amount_cents - $payment->allocatedCents();

        $bills = $payment->account->bills()
            ->whereIn('status', [Bill::STATUS_ISSUED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
            ->oldest('due_date')->get();

        foreach ($bills as $bill) {
            if ($remaining <= 0) {
                break;
            }
            $due = $bill->outstandingCents();
            if ($due <= 0) {
                continue;
            }
            $apply = min($remaining, $due);
            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'municipal_account_id' => $payment->municipal_account_id,
                'bill_id' => $bill->id,
                'amount_cents' => $apply,
                'allocated_by' => $actor?->id,
            ]);
            $bill->increment('amount_paid_cents', $apply);
            $bill->syncStatus();
            $remaining -= $apply;
        }

        if ($payment->fresh()->unallocatedCents() > 0) {
            $payment->forceFill(['reconciliation_status' => 'partially_allocated'])->save();
        }

        return $payment->amount_cents - $remaining; // allocated total
    }
}
