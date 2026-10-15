<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\ServiceInterruption;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Mobile/web dashboard summary — mirrors the resident portal dashboard. */
class DashboardController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $links = $user->approvedPropertyLinks()->with('property.account')->get();
        $properties = $links->pluck('property')->filter()->values();

        $selected = $request->filled('property_id')
            ? $properties->firstWhere('id', $request->integer('property_id'))
            : null;
        abort_if($request->filled('property_id') && ! $selected, 403);
        $selected ??= $properties->first();

        $account = $selected?->account;

        $openBills = $account?->bills()
            ->with('items')
            ->whereIn('status', [Bill::STATUS_ISSUED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
            ->get() ?? collect();

        $lastPayment = $account?->payments()
            ->where('status', Payment::STATUS_SUCCESSFUL)->latest('paid_at')->first();

        $interruptions = ServiceInterruption::current()
            ->where('service', 'water')
            ->when($selected, fn ($q) => $q->where(function ($q) use ($selected) {
                $q->whereNull('suburb')->orWhere('suburb', $selected->suburb)
                    ->orWhere('area', 'All areas');
            }))->limit(5)->get();

        return response()->json([
            'properties' => $properties->map(fn ($p) => $this->propertyData($p, $links->firstWhere('property_id', $p->id))),
            'selected_property' => $selected ? $this->propertyData($selected, $links->firstWhere('property_id', $selected->id)) : null,
            'account' => $account ? $this->accountData($account) : null,
            'summary' => [
                'outstanding_cents' => $account?->outstandingCents() ?? 0,
                'rates_cents' => $openBills->flatMap->items->where('charge_type', 'rates')->sum('amount_cents'),
                'water_cents' => $openBills->flatMap->items->where('charge_type', 'water')->sum('amount_cents'),
                'overdue_cents' => $account?->arrears_cents ?? 0,
                'next_due_date' => $openBills->sortBy('due_date')->first()?->due_date?->toDateString(),
                'last_payment' => $lastPayment ? $this->paymentData($lastPayment->loadMissing('account')) : null,
            ],
            'bills' => $account?->bills()->with('items')->latest('issue_date')->limit(6)->get()
                ->map(fn ($b) => $this->billData($b)) ?? [],
            'water_interruptions' => $interruptions->map(fn ($i) => $this->interruptionData($i)),
            'open_requests' => ServiceRequest::where('user_id', $user->id)
                ->whereIn('status', ServiceRequest::OPEN_STATUSES)->latest()->limit(5)->get()
                ->map(fn ($r) => $this->requestData($r)),
            'active_disconnection' => (bool) $account?->disconnectionNotices()
                ->whereNotIn('status', ['restored', 'cancelled'])->exists(),
            'unread_notifications' => $user->notifications()->whereNull('read_at')->count(),
        ]);
    }
}
