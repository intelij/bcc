<?php

namespace App\Services\Integrations\Municipal;

use App\Contracts\MunicipalBillingProviderInterface;
use App\Models\MunicipalAccount;

/**
 * MOCK municipal billing adapter — simulates the council's upstream billing
 * system. For the demo it "reads" the platform's own accounts and fabricates
 * plausible bill records, exercising the real sync pipeline end-to-end.
 * NOT a production integration.
 */
class MockMunicipalBillingProvider implements MunicipalBillingProviderInterface
{
    public function key(): string
    {
        return 'mock';
    }

    public function fetchAccounts(): array
    {
        return MunicipalAccount::with('property')->get()->map(fn ($a) => [
            'account_number' => $a->account_number,
            'holder_name' => $a->holder_name,
            'property_reference' => $a->property?->reference,
            'balance_cents' => $a->balance_cents,
            'status' => $a->status,
        ])->all();
    }

    public function fetchBills(string $accountNumber): array
    {
        // Simulate the upstream system issuing one bill per month for the last
        // two months when asked — deterministic so re-syncs are idempotent.
        $account = MunicipalAccount::where('account_number', $accountNumber)->first();
        if (! $account) {
            return [];
        }

        $bills = [];
        foreach ([2, 1] as $monthsAgo) {
            $period = now()->subMonths($monthsAgo);
            $bills[] = [
                'invoice_number' => sprintf('EXT-%s-%s', $accountNumber, $period->format('Ym')),
                'period_start' => $period->copy()->startOfMonth()->toDateString(),
                'period_end' => $period->copy()->endOfMonth()->toDateString(),
                'issue_date' => $period->copy()->startOfMonth()->toDateString(),
                'due_date' => $period->copy()->endOfMonth()->toDateString(),
                'items' => [
                    ['charge_type' => 'rates', 'description' => 'Property rates', 'quantity' => 1, 'unit_price_cents' => 4500, 'amount_cents' => 4500],
                    ['charge_type' => 'water', 'description' => 'Water consumption (est.)', 'quantity' => 1, 'unit_price_cents' => 2200, 'amount_cents' => 2200],
                    ['charge_type' => 'refuse', 'description' => 'Refuse collection', 'quantity' => 1, 'unit_price_cents' => 800, 'amount_cents' => 800],
                ],
            ];
        }

        return $bills;
    }

    public function fetchPayments(string $accountNumber): array
    {
        return []; // upstream payments not simulated
    }

    public function postPayment(array $paymentData): ?string
    {
        return 'UPSTREAM-'.strtoupper(bin2hex(random_bytes(5)));
    }
}
