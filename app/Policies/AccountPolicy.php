<?php

namespace App\Policies;

use App\Models\MunicipalAccount;
use App\Models\User;

class AccountPolicy
{
    public function view(User $user, MunicipalAccount $account): bool
    {
        if ($user->isStaff()) {
            return $user->hasPermission('accounts.view');
        }

        return $account->property && $user->canAccessProperty($account->property, 'can_view_account');
    }

    public function viewStatements(User $user, MunicipalAccount $account): bool
    {
        if ($user->isStaff()) {
            return $user->hasPermission('accounts.view');
        }

        return $account->property && $user->canAccessProperty($account->property, 'can_view_statements');
    }

    public function pay(User $user, MunicipalAccount $account): bool
    {
        return $account->property && $user->canAccessProperty($account->property, 'can_pay');
    }
}
