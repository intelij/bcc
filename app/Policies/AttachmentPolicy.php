<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ($attachment->uploaded_by === $user->id) {
            return true;
        }

        $parent = $attachment->attachable;

        return match (true) {
            $parent instanceof ServiceRequest => $parent->user_id === $user->id,
            $parent instanceof Payment => $parent->user_id === $user->id
                || ($parent->property && $user->canAccessProperty($parent->property, 'can_view_account')),
            default => false,
        };
    }
}
