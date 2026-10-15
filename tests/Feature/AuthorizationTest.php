<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Payment;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** IDOR / privilege-escalation coverage: users must never reach other users' data. */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_cannot_view_another_users_bill(): void
    {
        $intruder = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $bill = $this->makeBill($account);

        $this->actingAs($intruder)->get(route('bills.show', $bill))->assertForbidden();
    }

    public function test_resident_cannot_view_another_property(): void
    {
        $intruder = $this->makeUser();
        $account = $this->makePropertyWithAccount();

        $this->actingAs($intruder)->get(route('properties.show', $account->property))->assertForbidden();
    }

    public function test_resident_cannot_view_another_users_payment(): void
    {
        $intruder = $this->makeUser();
        $owner = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $this->link($owner, $account->property);
        $payment = Payment::create([
            'municipal_account_id' => $account->id, 'property_id' => $account->property_id,
            'user_id' => $owner->id, 'amount_cents' => 5000, 'provider' => 'mock', 'status' => 'successful',
        ]);

        $this->actingAs($intruder)->get(route('payments.show', $payment))->assertForbidden();
    }

    public function test_resident_cannot_view_anothers_statement(): void
    {
        $intruder = $this->makeUser();
        $account = $this->makePropertyWithAccount();

        $this->actingAs($intruder)->get(route('statements.show', $account))->assertForbidden();
        $this->actingAs($intruder)->get(route('statements.pdf', $account))->assertForbidden();
    }

    public function test_resident_cannot_pay_into_foreign_account(): void
    {
        $intruder = $this->makeUser();
        $account = $this->makePropertyWithAccount();

        $this->actingAs($intruder)->post(route('payments.store'), [
            'municipal_account_id' => $account->id, 'amount' => 10, 'provider' => 'mock',
        ])->assertForbidden();

        $this->assertDatabaseMissing('payments', ['municipal_account_id' => $account->id]);
    }

    public function test_tenant_without_bill_permission_cannot_view_bills(): void
    {
        $tenant = $this->makeUser('tenant');
        $account = $this->makePropertyWithAccount();
        $this->link($tenant, $account->property, 'tenant', ['can_view_bills' => false]);
        $bill = $this->makeBill($account);

        $this->actingAs($tenant)->get(route('bills.show', $bill))->assertForbidden();
    }

    public function test_tenant_without_pay_permission_cannot_pay(): void
    {
        $tenant = $this->makeUser('tenant');
        $account = $this->makePropertyWithAccount();
        $this->link($tenant, $account->property, 'tenant', ['can_pay' => false]);

        $this->actingAs($tenant)->post(route('payments.store'), [
            'municipal_account_id' => $account->id, 'amount' => 10, 'provider' => 'mock',
        ])->assertForbidden();
    }

    public function test_resident_cannot_access_admin_portal(): void
    {
        $resident = $this->makeUser();
        $this->actingAs($resident)->get('/admin')->assertForbidden();
    }

    public function test_customer_service_cannot_reconcile(): void
    {
        $cs = $this->makeUser('customer_service');
        $this->actingAs($cs)->get('/admin/reconciliation')->assertForbidden();
    }

    public function test_content_admin_cannot_view_payments(): void
    {
        $content = $this->makeUser('content_admin');
        $this->actingAs($content)->get('/admin/payments')->assertForbidden();
        $this->actingAs($content)->get('/admin')->assertOk();
    }

    public function test_finance_cannot_manage_users(): void
    {
        $finance = $this->makeUser('finance');
        $target = $this->makeUser();
        $this->actingAs($finance)->get('/admin/users')->assertForbidden();
        $this->actingAs($finance)->patch(route('admin.users.update', $target), ['status' => 'suspended'])->assertForbidden();
    }

    public function test_suspended_user_is_logged_out(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        $user->forceFill(['status' => 'suspended'])->save();

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_pending_property_link_grants_no_access(): void
    {
        $resident = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $this->link($resident, $account->property, 'owner', [])->forceFill(['status' => 'pending'])->save();
        $bill = $this->makeBill($account);

        $this->actingAs($resident)->get(route('bills.show', $bill))->assertForbidden();
    }

    public function test_unauthenticated_redirected_to_login(): void
    {
        $account = $this->makePropertyWithAccount();
        $bill = $this->makeBill($account);
        $this->get(route('bills.show', $bill))->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    protected function makeBill($account): Bill
    {
        return app(BillingService::class)->issueBill($account, [
            'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth(),
            'due_date' => now()->addDays(14),
        ], [['charge_type' => 'rates', 'description' => 'Rates', 'quantity' => 1, 'unit_price_cents' => 4500]]);
    }
}
