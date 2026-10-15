<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Bill;
use App\Models\MunicipalAccount;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyUser;
use App\Models\ServiceInterruption;
use App\Models\ServiceRequest;
use App\Models\User;

/**
 * Base controller for the v1 JSON API. Central serializers keep response
 * shapes consistent between the web-consumed API and the Flutter client.
 * Amounts are integer cents in the account currency (see DATABASE.md).
 */
abstract class ApiController extends Controller
{
    protected function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'email_verified' => $user->email_verified_at !== null,
            'is_staff' => $user->isStaff(),
            'roles' => $user->roles->pluck('slug')->values(),
            'permissions' => $user->roles->loadMissing('permissions')
                ->flatMap->permissions->pluck('slug')->unique()->values(),
        ];
    }

    protected function propertyData(Property $property, ?PropertyUser $link = null): array
    {
        return [
            'id' => $property->id,
            'reference' => $property->reference,
            'address' => $property->address_line,
            'suburb' => $property->suburb,
            'ward' => $property->ward,
            'city' => $property->city,
            'type' => $property->property_type,
            'status' => $property->status,
            'link' => $link ? [
                'relationship' => $link->relationship,
                'status' => $link->status,
                'capabilities' => [
                    'view_bills' => (bool) $link->can_view_bills,
                    'view_account' => (bool) $link->can_view_account,
                    'pay' => (bool) $link->can_pay,
                    'submit_requests' => (bool) $link->can_submit_requests,
                    'view_statements' => (bool) $link->can_view_statements,
                ],
            ] : null,
            'account' => $property->relationLoaded('account') && $property->account
                ? $this->accountData($property->account) : null,
        ];
    }

    protected function accountData(MunicipalAccount $account): array
    {
        return [
            'id' => $account->id,
            'account_number' => $account->account_number,
            'holder_name' => $account->holder_name,
            'currency' => $account->currency,
            'balance_cents' => $account->balance_cents,
            'arrears_cents' => $account->arrears_cents,
            'outstanding_cents' => $account->outstandingCents(),
            'status' => $account->status,
            'last_synced_at' => $account->last_synced_at?->toIso8601String(),
        ];
    }

    protected function billData(Bill $bill): array
    {
        return [
            'id' => $bill->id,
            'invoice_number' => $bill->invoice_number,
            'account_id' => $bill->municipal_account_id,
            'account_number' => $bill->relationLoaded('account') ? $bill->account?->account_number : null,
            'property_id' => $bill->property_id,
            'period_start' => $bill->period_start?->toDateString(),
            'period_end' => $bill->period_end?->toDateString(),
            'issue_date' => $bill->issue_date?->toDateString(),
            'due_date' => $bill->due_date?->toDateString(),
            'previous_balance_cents' => $bill->previous_balance_cents,
            'charges_cents' => $bill->charges_cents,
            'adjustments_cents' => $bill->adjustments_cents,
            'payments_cents' => $bill->payments_cents,
            'total_cents' => $bill->total_cents,
            'amount_paid_cents' => $bill->amount_paid_cents,
            'outstanding_cents' => $bill->outstandingCents(),
            'status' => $bill->status,
            'items' => $bill->relationLoaded('items')
                ? $bill->items->map(fn ($i) => [
                    'charge_type' => $i->charge_type,
                    'description' => $i->description,
                    'quantity' => (float) $i->quantity,
                    'unit_price_cents' => $i->unit_price_cents,
                    'amount_cents' => $i->amount_cents,
                ])->values() : null,
        ];
    }

    protected function paymentData(Payment $payment): array
    {
        return [
            'uuid' => $payment->uuid,
            'account_id' => $payment->municipal_account_id,
            'account_number' => $payment->relationLoaded('account') ? $payment->account?->account_number : null,
            'amount_cents' => $payment->amount_cents,
            'currency' => $payment->currency,
            'provider' => $payment->provider,
            'payment_method' => $payment->payment_method,
            'reference' => $payment->reference,
            'provider_reference' => $payment->provider_reference,
            'status' => $payment->status,
            'failure_reason' => $payment->failure_reason,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'created_at' => $payment->created_at?->toIso8601String(),
            'initiation' => $payment->meta['initiation'] ?? null,
            'allocations' => $payment->relationLoaded('allocations')
                ? $payment->allocations->map(fn ($a) => [
                    'bill_id' => $a->bill_id,
                    'invoice_number' => $a->relationLoaded('bill') ? $a->bill?->invoice_number : null,
                    'amount_cents' => $a->amount_cents,
                ])->values() : null,
        ];
    }

    protected function requestData(ServiceRequest $request): array
    {
        return [
            'id' => $request->id,
            'ticket_number' => $request->ticket_number,
            'category' => $request->category,
            'category_label' => $request->categoryLabel(),
            'description' => $request->description,
            'location' => $request->location_text,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'priority' => $request->priority,
            'status' => $request->status,
            'property_id' => $request->property_id,
            'created_at' => $request->created_at?->toIso8601String(),
            'resolved_at' => $request->resolved_at?->toIso8601String(),
            'comments' => $request->relationLoaded('publicComments')
                ? $request->publicComments->map(fn ($c) => [
                    'author' => $c->user?->name,
                    'staff' => $c->user?->isStaff() ?? false,
                    'body' => $c->body,
                    'created_at' => $c->created_at?->toIso8601String(),
                ])->values() : null,
            'attachments' => $request->relationLoaded('attachments')
                ? $request->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->original_name,
                    'mime' => $a->mime_type,
                    'url' => route('api.v1.attachments.show', $a),
                ])->values() : null,
        ];
    }

    protected function interruptionData(ServiceInterruption $i): array
    {
        return [
            'id' => $i->id,
            'title' => $i->title,
            'type' => $i->type,
            'service' => $i->service,
            'area' => $i->area,
            'suburb' => $i->suburb,
            'ward' => $i->ward,
            'starts_at' => $i->starts_at?->toIso8601String(),
            'expected_restoration_at' => $i->expected_restoration_at?->toIso8601String(),
            'resolved_at' => $i->resolved_at?->toIso8601String(),
            'reason' => $i->reason,
            'status' => $i->status,
        ];
    }

    protected function announcementData(Announcement $a): array
    {
        return [
            'slug' => $a->slug,
            'title' => $a->title,
            'body' => $a->body,
            'category' => $a->category,
            'priority' => $a->priority,
            'published_at' => $a->published_at?->toIso8601String(),
        ];
    }

    protected function notificationData(Notification $n): array
    {
        return [
            'id' => $n->id,
            'event' => $n->event,
            'title' => $n->title,
            'body' => $n->body,
            'data' => $n->data,
            'read' => $n->read_at !== null,
            'created_at' => $n->created_at?->toIso8601String(),
        ];
    }
}
