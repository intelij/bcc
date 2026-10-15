<?php

namespace App\Services\Integrations\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Manual bank transfer "provider". Initiation returns EFT instructions;
 * payment stays pending until a finance officer reviews the uploaded
 * proof of payment and approves/rejects/allocates it. No webhooks.
 */
class BankTransferProvider implements PaymentProviderInterface
{
    public function key(): string
    {
        return 'bank_transfer';
    }

    public function label(): string
    {
        return 'Bank transfer (EFT)';
    }

    public function initiate(Payment $payment): array
    {
        $bank = config('city.bank');

        return [
            'type' => 'instructions',
            'instructions' => [
                'bank' => $bank['bank'],
                'account_name' => $bank['account_name'],
                'account_number' => $bank['account_number'],
                'branch' => $bank['branch'],
                'reference' => $payment->account->account_number,
                'hint' => $bank['reference_hint'],
            ],
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        return false; // bank transfers never receive provider webhooks
    }

    public function parseWebhook(Request $request): ?array
    {
        return null;
    }
}
