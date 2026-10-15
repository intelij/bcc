<?php

namespace App\Policies;

use App\Models\Bill;
use App\Models\User;

class BillPolicy
{
    public function view(User $user, Bill $bill): bool
    {
        if ($user->isStaff()) {
            return $user->hasPermission('accounts.view');
        }

        return $user->canAccessProperty($bill->property, 'can_view_bills');
    }
}
