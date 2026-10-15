<?php

namespace App\Http\Controllers;

use App\Models\ServiceInterruption;
use App\Models\WaterSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaterController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $suburbs = $user->accessibleProperties()->pluck('suburb')->unique()->filter();

        $interruptions = ServiceInterruption::where('service', 'water')
            ->current()
            ->when($suburbs->isNotEmpty(), fn ($q) => $q->where(fn ($q2) => $q2
                ->whereIn('suburb', $suburbs)->orWhereNull('suburb')->orWhere('area', 'All areas')))
            ->get();

        $schedules = WaterSchedule::where('status', 'active')
            ->when($suburbs->isNotEmpty(), fn ($q) => $q->where(fn ($q2) => $q2
                ->whereIn('suburb', $suburbs)->orWhereNull('suburb')))
            ->orderBy('day_of_week')->get();

        $past = ServiceInterruption::where('service', 'water')
            ->where('status', 'resolved')->latest('resolved_at')->limit(10)->get();

        // Consumption history across linked meters.
        $meters = $user->accessibleProperties()->with('meters.readings')->get()
            ->flatMap->meters->unique('id');

        return view('water.index', compact('interruptions', 'schedules', 'past', 'meters'));
    }
}
