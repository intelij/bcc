<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        if ($user->isStaff()) {
            return $user->hasPermission('payments.view');
        }

        // Initiator, or any user with account access to the payment's property.
        if ($payment->user_id === $user->id) {
            return true;
        }

        return $payment->property && $user->canAccessProperty($payment->property, 'can_view_account');
    }
}
