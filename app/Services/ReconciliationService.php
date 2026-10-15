<?php

namespace App\Services;

use App\Models\BankPaymentImport;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Bank-file imports + matching/allocation for payments received outside
 * the gateway (EFT, deposits). Ambiguous rows are NEVER auto-allocated.
 */
class ReconciliationService
{
    public function __construct(
        protected PaymentService $payments,
        protected BillingService $billing,
        protected AuditService $audit,
    ) {}

    /**
     * Import a CSV of bank payments. Expected headers (case-insensitive):
     * date, reference, amount, [account_number], [payer]
     */
    public function importCsv(UploadedFile $file, User $staff): BankPaymentImport
    {
        $import = BankPaymentImport::create([
            'imported_by' => $staff->id, 'filename' => $file->getClientOriginalName(), 'status' => 'processed',
        ]);

        $handle = fopen($file->getRealPath(), 'r');
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), fgetcsv($handle) ?: []);

        $matched = $unmatched = 0;
        $total = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            if (! $data || empty($data['amount'])) {
                continue;
            }
            $total++;

            $amountCents = (int) round(((float) preg_replace('/[^0-9.\-]/', '', (string) $data['amount'])) * 100);
            $reference = trim((string) ($data['reference'] ?? ''));
            $accountNumber = trim((string) ($data['account_number'] ?? ''));

            $account = $this->matchAccount($accountNumber ?: $reference);

            $payment = Payment::create([
                'municipal_account_id' => $account?->id,
                'user_id' => null,
                'amount_cents' => $amountCents,
                'provider' => 'import',
                'payment_method' => 'bank_transfer',
                'reference' => $reference,
                'status' => Payment::STATUS_SUCCESSFUL, // funds received at bank; allocation pending
                'reconciliation_status' => $account ? 'matched' : 'unallocated',
                'source' => 'import',
                'paid_at' => isset($data['date']) ? now()->parse($data['date']) : now(),
                'meta' => ['import_id' => $import->id, 'payer' => $data['payer'] ?? null, 'unmatched' => ! $account],
            ]);

            if ($account) {
                $this->payments->allocateToBills($payment, $staff);
                $this->billing->recomputeAccount($account);
                $matched++;
            } else {
                $payment->forceFill(['status' => Payment::STATUS_UNALLOCATED])->save();
                $unmatched++;
            }
        }
        fclose($handle);

        $import->forceFill([
            'rows_total' => $total, 'rows_matched' => $matched, 'rows_unmatched' => $unmatched,
        ])->save();

        $this->audit->record('payment.import', $import, null, [
            'rows_total' => $total, 'matched' => $matched, 'unmatched' => $unmatched,
        ]);

        return $import;
    }

    /** Create a manual payment entry (e.g., cash at the counter). */
    public function recordManual(MunicipalAccount $account, int $amountCents, User $staff, ?string $reference = null): Payment
    {
        return DB::transaction(function () use ($account, $amountCents, $staff, $reference) {
            $payment = Payment::create([
                'municipal_account_id' => $account->id,
                'property_id' => $account->property_id,
                'user_id' => null,
                'amount_cents' => $amountCents,
                'provider' => 'manual',
                'payment_method' => 'cash',
                'reference' => $reference,
                'status' => Payment::STATUS_SUCCESSFUL,
                'reconciliation_status' => 'reconciled',
                'source' => 'manual',
                'paid_at' => now(),
            ]);

            $this->payments->allocateToBills($payment, $staff);
            $this->billing->recomputeAccount($account);
            $this->audit->record('payment.manual', $payment, null, ['amount_cents' => $amountCents]);

            return $payment;
        });
    }

    /**
     * Allocate an unallocated/matched payment to an account (+ its bills).
     */
    public function allocate(Payment $payment, MunicipalAccount $account, User $staff): Payment
    {
        return DB::transaction(function () use ($payment, $account, $staff) {
            $payment->forceFill([
                'municipal_account_id' => $account->id,
                'property_id' => $account->property_id,
                'status' => Payment::STATUS_SUCCESSFUL,
            ])->save();

            $this->payments->allocateToBills($payment, $staff);
            // Staff manually reviewed/allocated — remainder becomes account credit.
            $payment->forceFill(['reconciliation_status' => 'reconciled'])->save();
            $this->billing->recomputeAccount($account);
            $this->audit->record('payment.allocated', $payment, null, ['account' => $account->account_number]);

            return $payment->fresh();
        });
    }

    /** Best-effort account lookup from a reference string. */
    protected function matchAccount(string $reference): ?MunicipalAccount
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        // Exact account number match (case-insensitive).
        $acct = MunicipalAccount::whereRaw('LOWER(account_number) = ?', [strtolower($reference)])->first();
        if ($acct) {
            return $acct;
        }

        // Reference containing exactly one account number.
        $candidates = MunicipalAccount::all()->filter(
            fn ($a) => str_contains(strtoupper($reference), strtoupper($a->account_number))
        );

        return $candidates->count() === 1 ? $candidates->first() : null;
    }
}
