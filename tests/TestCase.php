<?php

namespace Tests;

use App\Models\MunicipalAccount;
use App\Models\Property;
use App\Models\PropertyUser;
use App\Models\Role;
use App\Models\User;
use App\Models\WaterMeter;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Only seed domain roles/templates when a migrated database is available
        // (tests using RefreshDatabase always have it).
        if (Schema::hasTable('roles')) {
            $this->seed([RolePermissionSeeder::class, NotificationTemplateSeeder::class]);
        }
    }

    protected function makeUser(string $role = 'resident', array $attrs = []): User
    {
        $user = User::factory()->create($attrs + [
            'email_verified_at' => now(),
            'password' => Hash::make('Password123'),
            'status' => 'active',
        ]);
        $user->roles()->attach(Role::where('slug', $role)->firstOrFail());

        return $user;
    }

    protected function makePropertyWithAccount(array $propertyAttrs = []): MunicipalAccount
    {
        $property = Property::create($propertyAttrs + [
            'reference' => 'PROP-'.fake()->unique()->numerify('######'),
            'address_line' => '1 Test Street', 'suburb' => 'Hillside', 'ward' => '8',
            'city' => 'Bulawayo', 'property_type' => 'residential',
            'status' => Property::STATUS_VERIFIED, 'verified_at' => now(),
        ]);

        WaterMeter::create(['meter_number' => 'WM-'.fake()->unique()->numerify('######'), 'property_id' => $property->id]);

        return MunicipalAccount::create([
            'account_number' => 'BYO-'.fake()->unique()->numerify('######'),
            'property_id' => $property->id,
            'holder_name' => 'Test Holder',
            'status' => 'active',
        ]);
    }

    /** Link $user to $property with the given relationship + permissions. */
    protected function link(User $user, Property $property, string $rel = 'owner', array $perms = []): PropertyUser
    {
        return PropertyUser::create([
            'property_id' => $property->id, 'user_id' => $user->id, 'relationship' => $rel,
            'status' => 'approved', 'authorized_at' => now(),
            'can_view_bills' => $perms['can_view_bills'] ?? true,
            'can_view_account' => $perms['can_view_account'] ?? true,
            'can_pay' => $perms['can_pay'] ?? true,
            'can_submit_requests' => $perms['can_submit_requests'] ?? true,
            'can_view_statements' => $perms['can_view_statements'] ?? true,
        ]);
    }
}
