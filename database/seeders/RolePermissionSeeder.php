<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'admin.access' => 'Access municipal portal',
        'users.view' => 'View/search users',
        'users.manage' => 'Manage users, roles, suspension',
        'properties.verify' => 'Verify property links',
        'accounts.view' => 'View municipal accounts',
        'billing.manage' => 'Issue and manage bills',
        'payments.view' => 'View payments',
        'payments.reconcile' => 'Reconcile and allocate payments',
        'disconnections.manage' => 'Manage disconnections',
        'requests.manage' => 'Manage service requests',
        'content.manage' => 'Manage announcements, water and refuse content',
        'notifications.send' => 'Send notifications and manage templates',
        'reports.view' => 'View and export reports',
        'integrations.manage' => 'Manage integrations and sync',
        'audit.view' => 'View audit logs',
        'settings.manage' => 'Manage system settings',
    ];

    public const MATRIX = [
        'admin' => self::PERMISSIONS_KEYS,
        'finance' => ['admin.access', 'accounts.view', 'billing.manage', 'payments.view', 'payments.reconcile', 'disconnections.manage', 'reports.view'],
        'customer_service' => ['admin.access', 'users.view', 'properties.verify', 'accounts.view', 'payments.view', 'requests.manage', 'notifications.send'],
        'content_admin' => ['admin.access', 'content.manage', 'notifications.send'],
        'resident' => [],
        'property_owner' => [],
        'tenant' => [],
    ];

    public const PERMISSIONS_KEYS = [
        'admin.access', 'users.view', 'users.manage', 'properties.verify', 'accounts.view',
        'billing.manage', 'payments.view', 'payments.reconcile', 'disconnections.manage',
        'requests.manage', 'content.manage', 'notifications.send', 'reports.view',
        'integrations.manage', 'audit.view', 'settings.manage',
    ];

    public const ROLES = [
        'resident' => 'Resident',
        'property_owner' => 'Property Owner',
        'tenant' => 'Tenant',
        'customer_service' => 'Customer Service Officer',
        'finance' => 'Finance Officer',
        'content_admin' => 'Content Administrator',
        'admin' => 'System Administrator',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $slug => $name) {
            Permission::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        foreach (self::ROLES as $slug => $name) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => $name]);
            $perms = self::MATRIX[$slug] ?? [];
            $role->permissions()->sync(Permission::whereIn('slug', $perms)->pluck('id'));
        }
    }
}
