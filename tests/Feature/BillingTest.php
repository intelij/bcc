<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_bill_creates_items_and_updates_account(): void
    {
        $account = $this->makePropertyWithAccount();
        $billing = app(BillingService::class);

        $bill = $billing->issueBill($account, [
            'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth(),
            'due_date' => now()->addDays(14),
        ], [
            ['charge_type' => 'rates', 'description' => 'Rates', 'quantity' => 1, 'unit_price_cents' => 4500],
            ['charge_type' => 'water', 'description' => 'Water', 'quantity' => 2, 'unit_price_cents' => 1100],
        ]);

        $this->assertEquals(6700, $bill->charges_cents);
        $this->assertEquals(6700, $bill->total_cents);
        $this->assertEquals(2, $bill->items()->count());
        $this->assertEquals(6700, $account->fresh()->balance_cents);
        $this->assertEquals('issued', $bill->status);
    }

    public function test_new_bill_notifies_linked_users(): void
    {
        $account = $this->makePropertyWithAccount();
        $user = $this->makeUser();
        $this->link($user, $account->property);

        app(BillingService::class)->issueBill($account, [
            'period_start' => now(), 'period_end' => now(), 'due_date' => now()->addWeek(),
        ], [['charge_type' => 'rates', 'description' => 'Rates', 'quantity' => 1, 'unit_price_cents' => 100]]);

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'event' => 'NEW_BILL']);
        $this->assertDatabaseHas('notification_deliveries', ['user_id' => $user->id, 'channel' => 'email', 'status' => 'sent']);
    }

    public function test_overdue_sweep_marks_bills(): void
    {
        $account = $this->makePropertyWithAccount();
        $bill = app(BillingService::class)->issueBill($account, [
            'period_start' => now()->subMonth()->startOfMonth(), 'period_end' => now()->subMonth()->endOfMonth(),
            'issue_date' => now()->subMonth(), 'due_date' => now()->subDays(5),
        ], [['charge_type' => 'rates', 'description' => 'R', 'quantity' => 1, 'unit_price_cents' => 1000]]);

        $count = app(BillingService::class)->markOverdue();

        $this->assertEquals('overdue', $bill->fresh()->status);
        $this->assertEquals(1000, $account->fresh()->arrears_cents);
    }

    public function test_statement_sums_correctly(): void
    {
        $account = $this->makePropertyWithAccount();
        $billing = app(BillingService::class);

        $billing->issueBill($account, [
            'period_start' => now()->subMonths(2), 'period_end' => now()->subMonths(2),
            'issue_date' => now()->subMonths(2), 'due_date' => now()->subMonths(2)->addDays(14),
        ], [['charge_type' => 'rates', 'description' => 'Old', 'quantity' => 1, 'unit_price_cents' => 2000]]);

        $billing->issueBill($account, [
            'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth(),
            'issue_date' => now(), 'due_date' => now()->addDays(14),
        ], [['charge_type' => 'rates', 'description' => 'New', 'quantity' => 1, 'unit_price_cents' => 3000]]);

        $stmt = $billing->statement($account, now()->startOfMonth()->toDateString(), now()->toDateString());

        $this->assertEquals(2000, $stmt['opening']); // previous bill is opening balance
        $this->assertEquals(5000, $stmt['closing']);
    }
}
