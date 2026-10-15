<?php

namespace App\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Every payment provider (EcoCash, OneMoney, bank transfer, card, mock...)
 * implements this adapter. Domain code NEVER talks to a concrete provider.
 */
interface PaymentProviderInterface
{
    /** Short provider key stored on payments.provider. */
    public function key(): string;

    public function label(): string;

    /**
     * Begin a payment. Returns initiation data for the caller:
     * ['type' => 'redirect'|'instructions'|'pending',
     *  'redirect_url' => ?, 'instructions' => ?, 'provider_reference' => ?]
     */
    public function initiate(Payment $payment): array;

    /**
     * Verify an inbound webhook signature/payload authenticity.
     * Must NEVER trust caller data without verification.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Normalize a webhook payload into:
     * ['event_id' => string, 'provider_reference' => string,
     *  'status' => 'successful'|'failed'|'cancelled'|'pending',
     *  'amount_cents' => int, 'currency' => string, 'paid_at' => ?string]
     * Returns null when the payload is not about a payment.
     */
    public function parseWebhook(Request $request): ?array;
}
