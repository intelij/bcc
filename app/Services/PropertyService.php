<?php

namespace App\Services;

use App\Models\MunicipalAccount;
use App\Models\Property;
use App\Models\PropertyUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** Property linking, verification, and tenant authorization. */
class PropertyService
{
    public function __construct(
        protected AuditService $audit,
        protected NotificationService $notifications,
    ) {}

    /**
     * A resident requests to link a municipal account number.
     * Creates a pending link that staff must approve — prevents
     * unauthorized linking to other people's properties.
     */
    public function requestLink(User $user, string $accountNumber, string $relationship = 'owner'): PropertyUser
    {
        $account = MunicipalAccount::where('account_number', strtoupper(trim($accountNumber)))->firstOr(
            fn () => throw new BadRequestHttpException('No municipal account found with that number.')
        );

        $property = $account->property;

        $existing = PropertyUser::where('property_id', $property->id)
            ->where('user_id', $user->id)
            ->where('relationship', $relationship)
            ->first();

        if ($existing && $existing->status !== PropertyUser::STATUS_REVOKED) {
            throw new BadRequestHttpException('You already have a link request for this property.');
        }

        $link = $existing ?? new PropertyUser([
            'property_id' => $property->id,
            'user_id' => $user->id,
            'relationship' => $relationship,
        ]);

        // Owners need staff verification; tenants start pending owner/staff approval.
        $link->fill([
            'status' => PropertyUser::STATUS_PENDING,
            'can_view_bills' => true, 'can_view_account' => true, 'can_pay' => true,
            'can_submit_requests' => true, 'can_view_statements' => $relationship !== PropertyUser::REL_TENANT ? true : false,
        ]);
        $link->save();

        $this->audit->record('property.link_requested', $property, null, [
            'user_id' => $user->id, 'relationship' => $relationship,
        ]);

        return $link;
    }

    /** Staff: approve or reject a pending link. */
    public function reviewLink(PropertyUser $link, bool $approve, User $staff, ?string $reason = null): PropertyUser
    {
        return DB::transaction(function () use ($link, $approve, $staff, $reason) {
            $link->forceFill([
                'status' => $approve ? PropertyUser::STATUS_APPROVED : PropertyUser::STATUS_REVOKED,
                'authorized_by' => $staff->id,
                'authorized_at' => now(),
            ])->save();

            $this->audit->record($approve ? 'property.link_approved' : 'property.link_rejected', $link);

            $this->notifications->notifyUser($link->user, 'ACCOUNT_UPDATED', [
                'title' => $approve ? 'Property linked' : 'Property link rejected',
                'message' => $approve
                    ? 'Your link to '.$link->property->fullAddress().' was approved.'
                    : 'Your link request for '.$link->property->fullAddress().' was rejected.'.($reason ? " Reason: $reason" : ''),
            ]);

            return $link;
        });
    }

    /** Owner authorizes/updates a tenant's access + explicit permissions. */
    public function authorizeTenant(User $owner, Property $property, User $tenant, array $permissions): PropertyUser
    {
        if (! $owner->canAccessProperty($property) || ! $owner->propertyUsers()
            ->where('property_id', $property->id)->where('relationship', PropertyUser::REL_OWNER)->where('status', 'approved')->exists()) {
            abort(403, 'Only a verified owner may authorize tenants.');
        }

        $link = PropertyUser::updateOrCreate(
            ['property_id' => $property->id, 'user_id' => $tenant->id, 'relationship' => PropertyUser::REL_TENANT],
            array_merge([
                'status' => PropertyUser::STATUS_APPROVED,
                'authorized_by' => $owner->id,
                'authorized_at' => now(),
            ], array_intersect_key($permissions, array_flip([
                'can_view_bills', 'can_view_account', 'can_pay', 'can_submit_requests', 'can_view_statements',
            ])))
        );

        $this->audit->record('property.tenant_authorized', $property, null, [
            'tenant_id' => $tenant->id, 'permissions' => $permissions,
        ]);

        $this->notifications->notifyUser($tenant, 'ACCOUNT_UPDATED', [
            'title' => 'Property access granted',
            'message' => $owner->name.' granted you access to '.$property->fullAddress().'.',
        ]);

        return $link;
    }

    public function revokeLink(PropertyUser $link, User $actor): void
    {
        $old = $link->only('status');
        $link->forceFill(['status' => PropertyUser::STATUS_REVOKED])->save();
        $this->audit->record('property.link_revoked', $link, $old, ['status' => 'revoked']);
    }
}
