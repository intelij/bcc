<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\PropertyUser;
use App\Models\User;

class PropertyPolicy
{
    public function view(User $user, Property $property): bool
    {
        return $user->isStaff() || $user->canAccessProperty($property);
    }

    public function viewBills(User $user, Property $property): bool
    {
        return $user->isStaff() || $user->canAccessProperty($property, 'can_view_bills');
    }

    public function viewAccount(User $user, Property $property): bool
    {
        return $user->isStaff() || $user->canAccessProperty($property, 'can_view_account');
    }

    public function viewStatements(User $user, Property $property): bool
    {
        return $user->isStaff() || $user->canAccessProperty($property, 'can_view_statements');
    }

    public function pay(User $user, Property $property): bool
    {
        return $user->isStaff() || $user->canAccessProperty($property, 'can_pay');
    }

    public function submitRequests(User $user, Property $property): bool
    {
        return $user->isStaff() || $user->canAccessProperty($property, 'can_submit_requests');
    }

    public function manageTenants(User $user, Property $property): bool
    {
        return $user->propertyUsers()
            ->where('property_id', $property->id)
            ->where('relationship', PropertyUser::REL_OWNER)
            ->where('status', PropertyUser::STATUS_APPROVED)
            ->exists() || $user->hasPermission('properties.verify');
    }
}
