<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\ServiceInterruption;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $links = $user->approvedPropertyLinks()->with('property.account')->get();
        $properties = $links->pluck('property')->filter();

        $selected = null;
        if ($request->filled('property')) {
            $selected = $properties->firstWhere('id', (int) $request->integer('property'));
            abort_if($request->filled('property') && ! $selected, 403);
        }
        $selected ??= $properties->first();

        $account = $selected?->account;
        $bills = $account?->bills()->with('items')->limit(6)->get() ?? collect();

        $openBills = $account?->bills()
            ->whereIn('status', [Bill::STATUS_ISSUED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])->get() ?? collect();

        $currentRates = $openBills->flatMap->items->where('charge_type', 'rates')->sum('amount_cents');
        $currentWater = $openBills->flatMap->items->where('charge_type', 'water')->sum('amount_cents');

        $lastPayment = $account?->payments()->where('status', Payment::STATUS_SUCCESSFUL)->latest('paid_at')->first();

        $interruptions = ServiceInterruption::current()
            ->where('service', 'water')
            ->when($selected, fn ($q) => $q->where(function ($q) use ($selected) {
                $q->whereNull('suburb')->orWhere('suburb', $selected->suburb)
                    ->orWhere('area', 'All areas');
            }))->limit(5)->get();

        $requests = ServiceRequest::where('user_id', $user->id)
            ->whereIn('status', ServiceRequest::OPEN_STATUSES)->latest()->limit(5)->get();

        $disconnection = $account?->disconnectionNotices()
            ->whereNotIn('status', ['restored', 'cancelled'])->first();

        return view('dashboard', [
            'properties' => $properties,
            'links' => $links,
            'selected' => $selected,
            'account' => $account,
            'bills' => $bills,
            'outstanding' => $account?->outstandingCents() ?? 0,
            'currentRates' => $currentRates,
            'currentWater' => $currentWater,
            'overdue' => $account?->arrears_cents ?? 0,
            'nextDue' => $openBills->sortBy('due_date')->first()?->due_date,
            'lastPayment' => $lastPayment,
            'interruptions' => $interruptions,
            'openRequests' => $requests,
            'disconnection' => $disconnection,
            'unreadNotifications' => $user->notifications()->whereNull('read_at')->count(),
        ]);
    }
}
