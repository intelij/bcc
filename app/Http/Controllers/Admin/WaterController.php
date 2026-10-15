<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceInterruption;
use App\Models\User;
use App\Models\WaterSchedule;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaterController extends Controller
{
    public function __construct(
        protected AuditService $audit,
        protected NotificationService $notifications,
    ) {}

    public function index(): View
    {
        return view('admin.water.index', [
            'interruptions' => ServiceInterruption::where('service', 'water')->latest()->paginate(15),
            'schedules' => WaterSchedule::orderBy('area')->orderBy('day_of_week')->get(),
            'days' => WaterSchedule::DAYS,
        ]);
    }

    public function storeInterruption(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:planned,emergency,maintenance',
            'area' => 'required|string|max:255',
            'suburb' => 'nullable|string|max:255',
            'ward' => 'nullable|string|max:50',
            'starts_at' => 'required|date',
            'expected_restoration_at' => 'nullable|date|after_or_equal:starts_at',
            'reason' => 'nullable|string|max:500',
            'status' => 'required|in:planned,active',
        ]);

        $interruption = ServiceInterruption::create($data + [
            'service' => 'water', 'created_by' => $request->user()->id,
        ]);

        $this->audit->record('water.interruption_created', $interruption);

        // Targeted notification: residents whose property is in the affected suburb/ward.
        $users = User::whereHas('propertyUsers', fn ($q) => $q
            ->where('property_users.status', 'approved')
            ->whereHas('property', fn ($p) => $p
                ->when($data['suburb'], fn ($s) => $s->where('suburb', $data['suburb']))
                ->when($data['ward'], fn ($w) => $w->where('ward', $data['ward']))))
            ->get();

        $this->notifications->notify($users->all(), 'WATER_INTERRUPTION', [
            'title' => $interruption->title,
            'message' => "{$interruption->title}: {$interruption->area} from ".$interruption->starts_at->toFormattedDateString().'. '.($interruption->reason ?? ''),
        ]);

        return back()->with('success', 'Interruption published; '.$users->count().' affected residents notified.');
    }

    public function updateInterruption(Request $request, ServiceInterruption $interruption): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', ServiceInterruption::STATUSES),
            'expected_restoration_at' => 'nullable|date',
        ]);

        $old = $interruption->only('status');
        $interruption->forceFill([
            'status' => $data['status'],
            'expected_restoration_at' => $data['expected_restoration_at'] ?? $interruption->expected_restoration_at,
            'resolved_at' => $data['status'] === 'resolved' ? now() : $interruption->resolved_at,
        ])->save();

        $this->audit->record('water.interruption_updated', $interruption, $old, $interruption->only('status'));

        if ($data['status'] === ServiceInterruption::STATUS_RESOLVED) {
            $users = User::whereHas('propertyUsers', fn ($q) => $q
                ->where('property_users.status', 'approved')
                ->whereHas('property', fn ($p) => $p
                    ->when($interruption->suburb, fn ($s) => $s->where('suburb', $interruption->suburb))))
                ->get();
            $this->notifications->notify($users->all(), 'SERVICE_RESTORED', [
                'title' => 'Water restored',
                'message' => "Supply restored to {$interruption->area}.",
            ]);
        }

        return back()->with('success', 'Interruption updated.');
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'area' => 'required|string|max:255',
            'suburb' => 'nullable|string|max:255',
            'ward' => 'nullable|string|max:50',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ]);

        WaterSchedule::create($data + ['status' => 'active', 'created_by' => $request->user()->id]);

        return back()->with('success', 'Water schedule added.');
    }

    public function destroySchedule(WaterSchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return back()->with('success', 'Schedule removed.');
    }
}
