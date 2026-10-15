<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * DemoPay mock checkout — simulates the provider's hosted payment page.
 * The outcome is delivered by a *signed webhook* through the same code path
 * a real gateway would use. Returning from this page NEVER finalizes payment.
 */
class MockCheckoutController extends Controller
{
    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);
        abort_unless($payment->provider === 'mock', 404);

        return view('mock-checkout', compact('payment'));
    }

    /**
     * The mock provider "processes" the payment then fires a signed webhook —
     * same trust boundary as a real provider callback.
     */
    public function complete(Request $request, Payment $payment, PaymentService $service): Response
    {
        $this->authorize('view', $payment);
        abort_unless($payment->provider === 'mock', 404);

        $outcome = $request->validate(['outcome' => 'required|in:success,fail,cancel'])['outcome'];

        $eventType = match ($outcome) {
            'success' => 'payment.completed',
            'fail' => 'payment.failed',
            'cancel' => 'payment.cancelled',
        };

        // Build the exact request a real provider would POST to our webhook.
        $body = json_encode([
            'event_id' => (string) Str::uuid(),
            'event_type' => $eventType,
            'payment_uuid' => $payment->uuid,
            'provider_reference' => $payment->provider_reference,
            'amount_cents' => $payment->amount_cents,
            'currency' => $payment->currency,
            'paid_at' => now()->toIso8601String(),
            'failure_reason' => $outcome === 'fail' ? 'Insufficient funds (mock)' : null,
        ]);

        $secret = config('integrations.payments.providers.mock.secret');
        $webhookRequest = Request::create(
            url('/webhooks/payments/mock'), 'POST', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PROVIDER_SIGNATURE' => hash_hmac('sha256', $body, $secret),
            ], $body
        );

        $service->handleWebhook('mock', $webhookRequest);

        $payment->refresh();

        return redirect()->route('payments.show', $payment->uuid)
            ->with('success', match ($payment->status) {
                'successful' => 'Payment confirmed by provider.',
                'failed' => 'Payment failed.',
                'cancelled' => 'Payment cancelled.',
                default => 'Payment is being processed.',
            });
    }
}
