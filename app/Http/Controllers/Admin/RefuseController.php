<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefuseSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefuseController extends Controller
{
    public function index(): View
    {
        return view('admin.refuse.index', [
            'schedules' => RefuseSchedule::orderBy('area')->orderBy('collection_day')->paginate(20),
            'days' => RefuseSchedule::DAYS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'area' => 'required|string|max:255',
            'suburb' => 'nullable|string|max:255',
            'ward' => 'nullable|string|max:50',
            'collection_day' => 'required|integer|min:0|max:6',
            'frequency' => 'required|in:weekly,fortnightly',
            'next_collection_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        RefuseSchedule::create($data + ['status' => 'active', 'created_by' => $request->user()->id]);

        return back()->with('success', 'Refuse schedule added.');
    }

    public function update(Request $request, RefuseSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:active,suspended,delayed',
            'next_collection_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $schedule->update($data);

        return back()->with('success', 'Schedule updated.');
    }
}
