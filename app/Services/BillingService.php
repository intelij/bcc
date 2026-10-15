<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Billing domain logic: issuing bills, balances, statements, overdue sweeps. */
class BillingService
{
    public function __construct(
        protected NotificationService $notifications,
        protected AuditService $audit,
    ) {}

    /**
     * Issue a bill to an account.
     * $items: array of [charge_type, description, quantity, unit_price_cents, amount_cents?, meter_reading_id?]
     */
    public function issueBill(MunicipalAccount $account, array $data, array $items, ?User $issuer = null): Bill
    {
        $user = $issuer ?? auth()->user();

        return DB::transaction(function () use ($account, $data, $items) {
            $charges = collect($items)->sum(fn ($i) => (int) ($i['amount_cents'] ?? ($i['quantity'] ?? 1) * $i['unit_price_cents']));

            $bill = Bill::create([
                'invoice_number' => $data['invoice_number'] ?? $this->nextInvoiceNumber($account),
                'municipal_account_id' => $account->id,
                'property_id' => $account->property_id,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'issue_date' => $data['issue_date'] ?? today(),
                'due_date' => $data['due_date'],
                'previous_balance_cents' => $account->balance_cents,
                'charges_cents' => $charges,
                'adjustments_cents' => $data['adjustments_cents'] ?? 0,
                'arrears_cents' => max(0, $account->balance_cents),
                'total_cents' => $charges + ($data['adjustments_cents'] ?? 0) + max(0, $account->balance_cents),
                'status' => Bill::STATUS_ISSUED,
            ]);

            foreach ($items as $item) {
                $bill->items()->create([
                    'charge_type' => $item['charge_type'] ?? BillItem::TYPE_OTHER,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price_cents' => $item['unit_price_cents'] ?? 0,
                    'amount_cents' => $item['amount_cents'] ?? (($item['quantity'] ?? 1) * ($item['unit_price_cents'] ?? 0)),
                    'meter_reading_id' => $item['meter_reading_id'] ?? null,
                ]);
            }

            $this->recomputeAccount($account);

            $this->audit->record('bill.issued', $bill, null, [
                'invoice_number' => $bill->invoice_number, 'total_cents' => $bill->total_cents,
            ]);

            $this->notifyAccountUsers($account, 'NEW_BILL', [
                'title' => 'New municipal bill issued',
                'message' => 'Bill '.$bill->invoice_number.' for '.money($bill->total_cents).' is due on '.$bill->due_date->toFormattedDateString().'.',
                'data' => ['bill_id' => $bill->id],
            ]);

            return $bill;
        });
    }

    public function nextInvoiceNumber(MunicipalAccount $account): string
    {
        $seq = Bill::where('municipal_account_id', $account->id)->count() + 1;

        return sprintf('INV-%s-%s-%03d', $account->account_number, now()->format('Ym'), $seq);
    }

    /** Recompute cached account balance + arrears from bills and payments. */
    public function recomputeAccount(MunicipalAccount $account): void
    {
        $outstanding = (int) $account->bills()
            ->whereIn('status', [Bill::STATUS_ISSUED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
            ->get()->sum->outstandingCents();

        $arrears = (int) $account->bills()
            ->where('status', Bill::STATUS_OVERDUE)
            ->get()->sum->outstandingCents();

        // Successful payments not yet allocated to bills count as credit.
        $credit = (int) $account->payments()
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->get()->sum->unallocatedCents();

        $account->forceFill([
            'balance_cents' => $outstanding - $credit,
            'arrears_cents' => $arrears,
        ])->save();
    }

    /** Daily sweep: issued bills past due become overdue; recalc arrears. */
    public function markOverdue(): int
    {
        $count = Bill::where('status', Bill::STATUS_ISSUED)
            ->where('due_date', '<', today())
            ->update(['status' => Bill::STATUS_OVERDUE]);

        MunicipalAccount::chunkById(100, fn ($accounts) => $accounts->each(fn ($a) => $this->recomputeAccount($a)));

        return $count;
    }

    /**
     * Statement data for an account over a period:
     * opening balance, itemized bills/payments/adjustments, closing balance.
     */
    public function statement(MunicipalAccount $account, ?string $from = null, ?string $to = null): array
    {
        $from = $from ? now()->parse($from)->startOfDay() : now()->subMonths(6)->startOfDay();
        $to = $to ? now()->parse($to)->endOfDay() : now()->endOfDay();

        $billsBefore = $account->bills()->where('issue_date', '<', $from)->get();
        $opening = $billsBefore->sum->outstandingCents();

        $bills = $account->bills()
            ->whereBetween('issue_date', [$from, $to])
            ->orderBy('issue_date')->get();

        $payments = $account->payments()
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->whereBetween('paid_at', [$from, $to])
            ->orderBy('paid_at')->get();

        // Merge into chronological ledger lines.
        $lines = collect();
        foreach ($bills as $bill) {
            foreach ($bill->items as $item) {
                $lines->push((object) [
                    'date' => $bill->issue_date, 'kind' => 'charge',
                    'description' => $bill->invoice_number.' — '.$item->description,
                    'amount_cents' => $item->amount_cents,
                ]);
            }
            if ($bill->adjustments_cents != 0) {
                $lines->push((object) [
                    'date' => $bill->issue_date, 'kind' => 'adjustment',
                    'description' => $bill->invoice_number.' — Adjustment',
                    'amount_cents' => $bill->adjustments_cents,
                ]);
            }
        }
        foreach ($payments as $payment) {
            $lines->push((object) [
                'date' => $payment->paid_at, 'kind' => 'payment',
                'description' => 'Payment '.$payment->uuid.' ('.$payment->provider.')',
                'amount_cents' => -$payment->amount_cents,
            ]);
        }
        $lines = $lines->sortBy('date')->values();

        $closing = $opening + $lines->sum('amount_cents');

        return compact('account', 'from', 'to', 'opening', 'lines', 'closing', 'bills', 'payments');
    }

    /** Notify all users linked to the account's property. */
    protected function notifyAccountUsers(MunicipalAccount $account, string $event, array $context): void
    {
        $users = $account->property?->propertyUsers()
            ->where('property_users.status', 'approved')
            ->with('user')->get()->pluck('user')->filter() ?? collect();

        foreach ($users as $user) {
            $this->notifications->notifyUser($user, $event, $context);
        }
    }
}
