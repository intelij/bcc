<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DisconnectionNotice;
use App\Models\MunicipalAccount;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisconnectionController extends Controller
{
    public function __construct(
        protected AuditService $audit,
        protected NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $notices = DisconnectionNotice::with('account.property')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.disconnections.index', [
            'notices' => $notices,
            'statuses' => DisconnectionNotice::STATUSES,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.disconnections.create', [
            'accounts' => MunicipalAccount::with('property')->where('arrears_cents', '>', 0)->orderBy('account_number')->get(),
            'selectedAccount' => MunicipalAccount::find($request->integer('account')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'municipal_account_id' => 'required|exists:municipal_accounts,id',
            'scheduled_date' => 'required|date|after:today',
            'reason' => 'required|string|max:500',
        ]);

        $account = MunicipalAccount::with('property')->findOrFail($data['municipal_account_id']);

        $notice = DisconnectionNotice::create([
            'municipal_account_id' => $account->id,
            'property_id' => $account->property_id,
            'arrears_cents' => $account->arrears_cents,
            'status' => DisconnectionNotice::STATUS_SCHEDULED,
            'notice_date' => today(),
            'scheduled_date' => $data['scheduled_date'],
            'reason' => $data['reason'],
            'created_by' => $request->user()->id,
        ]);

        $this->audit->record('disconnection.scheduled', $notice);

        $this->notifyResidents($account, 'DISCONNECTION_SCHEDULED', [
            'title' => 'Disconnection scheduled',
            'message' => 'Water supply to '.$account->property->fullAddress().' is scheduled for disconnection on '.$notice->scheduled_date->toFormattedDateString().' due to arrears of '.money($account->arrears_cents).'.',
        ]);

        return redirect()->route('admin.disconnections.index')->with('success', 'Disconnection scheduled.');
    }

    public function transition(Request $request, DisconnectionNotice $notice): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', DisconnectionNotice::STATUSES),
        ]);

        $old = $notice->only('status');
        $notice->forceFill([
            'status' => $data['status'],
            'disconnected_at' => $data['status'] === 'disconnected' ? now() : $notice->disconnected_at,
            'restored_at' => $data['status'] === 'restored' ? now() : $notice->restored_at,
        ])->save();

        $this->audit->record('disconnection.status_changed', $notice, $old, $notice->only('status'));

        $event = match ($data['status']) {
            'restored' => 'SERVICE_RESTORED',
            'warning', 'notice_issued' => 'DISCONNECTION_WARNING',
            default => null,
        };

        if ($event) {
            $this->notifyResidents($notice->account, $event, [
                'title' => 'Service update',
                'message' => 'Disconnection status for '.$notice->property->fullAddress().' changed to '.status_label($data['status']).'.',
            ]);
        }

        return back()->with('success', 'Status updated.');
    }

    protected function notifyResidents(MunicipalAccount $account, string $event, array $context): void
    {
        $users = $account->property?->propertyUsers()
            ->where('property_users.status', 'approved')->with('user')->get()->pluck('user')->filter() ?? collect();
        $this->notifications->notify($users->all(), $event, $context);
    }
}
