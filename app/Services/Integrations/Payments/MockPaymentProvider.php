<?php

namespace App\Services\Integrations\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * MOCK PROVIDER — DemoPay. Simulates a hosted-checkout mobile-money provider.
 *
 * Flow: initiate() returns a redirect to the app's mock checkout page, where
 * the "customer" chooses an outcome. The mock checkout then POSTs a signed
 * payload to /webhooks/payments/mock exactly as a real gateway would.
 * NOT a production integration.
 */
class MockPaymentProvider implements PaymentProviderInterface
{
    public function key(): string
    {
        return 'mock';
    }

    public function label(): string
    {
        return 'DemoPay (mock provider)';
    }

    public function initiate(Payment $payment): array
    {
        $providerRef = 'MOCK-'.strtoupper(substr($payment->uuid, 0, 12));

        $payment->forceFill([
            'provider_reference' => $providerRef,
            'status' => Payment::STATUS_PENDING,
        ])->save();

        return [
            'type' => 'redirect',
            'redirect_url' => route('mock.checkout.show', $payment->uuid),
            'provider_reference' => $providerRef,
        ];
    }

    /** HMAC-SHA256 over the exact raw body, sent in X-Provider-Signature. */
    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('X-Provider-Signature');
        if (! $signature) {
            return false;
        }

        $secret = config('integrations.payments.providers.mock.secret');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(Request $request): ?array
    {
        $payload = $request->json()->all() ?: $request->request->all();
        if (empty($payload['payment_uuid']) || empty($payload['event_type'])) {
            return null;
        }

        $status = match ($payload['event_type']) {
            'payment.completed' => 'successful',
            'payment.failed' => 'failed',
            'payment.cancelled' => 'cancelled',
            default => 'pending',
        };

        return [
            'event_id' => $payload['event_id'] ?? null,
            'event_type' => $payload['event_type'],
            'payment_uuid' => $payload['payment_uuid'],
            'provider_reference' => $payload['provider_reference'] ?? null,
            'status' => $status,
            'amount_cents' => (int) ($payload['amount_cents'] ?? 0),
            'currency' => $payload['currency'] ?? 'USD',
            'paid_at' => $payload['paid_at'] ?? null,
            'failure_reason' => $payload['failure_reason'] ?? null,
        ];
    }

    /** Build a signed webhook payload — used by the mock checkout page. */
    public static function signedPayload(array $fields): array
    {
        $body = json_encode($fields);

        return [
            'body' => $fields,
            'signature' => hash_hmac('sha256', $body, config('integrations.payments.providers.mock.secret')),
        ];
    }
}
