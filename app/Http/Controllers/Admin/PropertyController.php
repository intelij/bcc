<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyUser;
use App\Services\AuditService;
use App\Services\PropertyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function index(Request $request): View
    {
        $properties = Property::with('account')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('address_line', 'like', '%'.$request->q.'%')
                ->orWhere('suburb', 'like', '%'.$request->q.'%')
                ->orWhere('reference', 'like', '%'.$request->q.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('ward'), fn ($q) => $q->where('ward', $request->string('ward')))
            ->latest()->paginate(20)->withQueryString();

        $pendingLinks = PropertyUser::with('property', 'user')
            ->where('status', PropertyUser::STATUS_PENDING)->latest()->get();

        return view('admin.properties.index', compact('properties', 'pendingLinks'));
    }

    public function show(Property $property): View
    {
        $property->load('account.bills', 'propertyUsers.user', 'meters.readings', 'disconnectionNotices');

        return view('admin.properties.show', compact('property'));
    }

    public function setStatus(Request $request, Property $property, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', Property::STATUSES),
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $old = $property->only('status');
        $property->forceFill([
            'status' => $data['status'],
            'verified_by' => $data['status'] === Property::STATUS_VERIFIED ? $request->user()->id : null,
            'verified_at' => $data['status'] === Property::STATUS_VERIFIED ? now() : null,
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ])->save();

        $audit->record('property.status_changed', $property, $old, $property->only('status'));

        return back()->with('success', 'Property status updated.');
    }

    public function reviewLink(Request $request, PropertyUser $link, PropertyService $properties): RedirectResponse
    {
        $data = $request->validate(['action' => 'required|in:approve,reject', 'reason' => 'nullable|string|max:500']);

        $properties->reviewLink($link, $data['action'] === 'approve', $request->user(), $data['reason'] ?? null);

        return back()->with('success', 'Link '.$data['action'].'d.');
    }
}
