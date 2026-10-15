<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Payment;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** API v1 coverage: token auth, resource scoping, idempotent payments, devices. */
class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_meta_is_public(): void
    {
        $this->getJson('/api/v1/meta')->assertOk()
            ->assertJsonPath('city.name', config('city.name'))
            ->assertJsonStructure(['request_categories', 'announcement_categories']);
    }

    public function test_register_and_login_issue_tokens(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Api Resident', 'email' => 'api@example.test', 'phone' => '+263771234567',
            'user_type' => 'resident', 'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertCreated()->assertJsonStructure(['token', 'user' => ['roles', 'permissions']]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'api@example.test', 'password' => 'Password123',
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'api@example.test', 'password' => 'wrong',
        ])->assertUnprocessable();
    }

    public function test_protected_endpoints_require_token(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/bills')->assertUnauthorized();
        $this->getJson('/api/v1/devices')->assertUnauthorized();
    }

    public function test_logout_revokes_token(): void
    {
        $user = $this->makeUser();
        $token = $this->token($user);

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_dashboard_scoped_to_linked_properties(): void
    {
        $user = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $this->link($user, $account->property);
        $this->makePropertyWithAccount(); // unrelated — must not leak

        $res = $this->withToken($this->token($user))->getJson('/api/v1/dashboard')->assertOk();

        $this->assertCount(1, $res->json('properties'));
        $this->assertSame($account->account_number, $res->json('account.account_number'));
    }

    public function test_bill_idor_blocked_over_api(): void
    {
        $intruder = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $bill = app(BillingService::class)->issueBill($account, [
            'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth(),
            'due_date' => now()->addDays(14),
        ], [['charge_type' => 'rates', 'description' => 'Rates', 'quantity' => 1, 'unit_price_cents' => 4500]]);

        $this->withToken($this->token($intruder))->getJson("/api/v1/bills/{$bill->id}")->assertForbidden();
    }

    public function test_payment_initiation_is_idempotent(): void
    {
        $user = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $this->link($user, $account->property);
        $token = $this->token($user);

        $payload = [
            'municipal_account_id' => $account->id, 'amount' => 25.00,
            'provider' => 'bank_transfer', 'idempotency_key' => 'test-idem-1',
        ];

        $first = $this->withToken($token)->postJson('/api/v1/payments', $payload)->assertCreated();
        $second = $this->withToken($token)->postJson('/api/v1/payments', $payload)->assertOk();

        $this->assertSame($first->json('data.uuid'), $second->json('data.uuid'));
        $this->assertSame(1, Payment::where('idempotency_key', 'test-idem-1')->count());
    }

    public function test_payment_into_foreign_account_forbidden(): void
    {
        $intruder = $this->makeUser();
        $account = $this->makePropertyWithAccount();

        $this->withToken($this->token($intruder))->postJson('/api/v1/payments', [
            'municipal_account_id' => $account->id, 'amount' => 10, 'provider' => 'mock',
        ])->assertForbidden();
    }

    public function test_devices_register_and_revoke(): void
    {
        $user = $this->makeUser();
        $token = $this->token($user);

        $this->withToken($token)->postJson('/api/v1/devices', [
            'device_id' => 'dev-1', 'platform' => 'android',
            'push_token' => 'push-abc', 'app_version' => '1.0.0',
        ])->assertCreated();

        $this->assertDatabaseHas('devices', ['user_id' => $user->id, 'device_id' => 'dev-1', 'status' => 'active']);

        $this->withToken($token)->deleteJson('/api/v1/devices/dev-1')->assertOk();
        $this->assertSame('revoked', Device::where('device_id', 'dev-1')->first()->status);
    }

    public function test_suspended_user_token_blocked(): void
    {
        $user = $this->makeUser();
        $token = $this->token($user);
        $user->forceFill(['status' => 'suspended'])->save();

        $this->withToken($token)->getJson('/api/v1/me')->assertForbidden()
            ->assertJsonPath('error', 'account_suspended');
        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_preferences_roundtrip(): void
    {
        $user = $this->makeUser();
        $token = $this->token($user);

        $this->withToken($token)->putJson('/api/v1/preferences', [
            'preferences' => ['PAYMENT_RECEIVED' => ['sms' => true, 'whatsapp' => true]],
        ])->assertOk()->assertJsonPath('data.PAYMENT_RECEIVED.sms', true);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id, 'event' => 'PAYMENT_RECEIVED', 'channel' => 'sms', 'enabled' => true,
        ]);

        $this->withToken($token)->putJson('/api/v1/preferences', [
            'preferences' => ['BOGUS_EVENT' => ['sms' => true]],
        ])->assertUnprocessable();
    }

    public function test_service_request_create_and_comment(): void
    {
        $user = $this->makeUser();
        $token = $this->token($user);

        $create = $this->withToken($token)->postJson('/api/v1/requests', [
            'category' => 'water_leak', 'description' => 'Pipe burst on corner', 'priority' => 'high',
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->withToken($token)->getJson("/api/v1/requests/{$id}")->assertOk();
        $this->withToken($token)->postJson("/api/v1/requests/{$id}/comments", [
            'body' => 'Still leaking.',
        ])->assertCreated();
    }
}
