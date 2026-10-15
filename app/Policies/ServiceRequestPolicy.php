<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function view(User $user, ServiceRequest $request): bool
    {
        if ($user->isStaff()) {
            return $user->hasPermission('requests.manage') || $user->hasPermission('users.view');
        }

        return $request->user_id === $user->id;
    }

    public function manage(User $user, ServiceRequest $request): bool
    {
        return $user->hasPermission('requests.manage');
    }

    public function comment(User $user, ServiceRequest $request): bool
    {
        return $this->view($user, $request);
    }
}
