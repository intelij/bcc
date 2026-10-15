<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\DisconnectionNotice;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Models\Property;
use App\Models\ServiceInterruption;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'properties' => Property::count(),
            'accounts' => MunicipalAccount::count(),
            'bills_issued' => Bill::where('status', '!=', 'draft')->count(),
            'amount_billed' => Bill::where('status', '!=', 'draft')->sum('total_cents'),
            'amount_collected' => Payment::where('status', Payment::STATUS_SUCCESSFUL)->sum('amount_cents'),
            'outstanding' => MunicipalAccount::sum('balance_cents'),
            'arrears' => MunicipalAccount::sum('arrears_cents'),
            'payments_today' => Payment::where('status', Payment::STATUS_SUCCESSFUL)->whereDate('paid_at', today())->count(),
            'failed_payments' => Payment::where('status', Payment::STATUS_FAILED)->count(),
            'unallocated' => Payment::where('status', Payment::STATUS_UNALLOCATED)->count(),
            'open_requests' => ServiceRequest::whereIn('status', ServiceRequest::OPEN_STATUSES)->count(),
            'active_interruptions' => ServiceInterruption::whereIn('status', ['active', 'planned', 'restoring'])->count(),
            'disconnections_active' => DisconnectionNotice::where('status', 'disconnected')->count(),
        ];

        // Chart data: collections per day (14d), billing per month (6m), channels.
        $collections = Payment::where('status', Payment::STATUS_SUCCESSFUL)
            ->where('paid_at', '>=', now()->subDays(14))
            ->selectRaw('date(paid_at) as day, SUM(amount_cents) as total')
            ->groupBy('day')->orderBy('day')->pluck('total', 'day');

        $billing = Bill::where('issue_date', '>=', now()->subMonths(6))
            ->selectRaw("strftime('%Y-%m', issue_date) as month, SUM(total_cents) as total")
            ->groupBy('month')->orderBy('month')->pluck('total', 'month');

        $channels = Payment::where('status', Payment::STATUS_SUCCESSFUL)
            ->selectRaw('provider, SUM(amount_cents) as total')->groupBy('provider')->pluck('total', 'provider');

        $requestsByStatus = ServiceRequest::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.dashboard', compact('stats', 'collections', 'billing', 'channels', 'requestsByStatus'));
    }
}
