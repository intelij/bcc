<?php

namespace App\Http\Controllers;

use App\Models\RefuseSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefuseController extends Controller
{
    public function index(Request $request): View
    {
        $suburbs = $request->user()->accessibleProperties()->pluck('suburb')->unique()->filter();

        $schedules = RefuseSchedule::orderBy('collection_day')
            ->when($suburbs->isNotEmpty(), fn ($q) => $q->where(fn ($q2) => $q2
                ->whereIn('suburb', $suburbs)->orWhereNull('suburb')))
            ->get();

        $all = RefuseSchedule::orderBy('collection_day')->get();

        return view('refuse.index', compact('schedules', 'all'));
    }
}
