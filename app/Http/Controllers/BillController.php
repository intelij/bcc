<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $propertyIds = $user->accessibleProperties('can_view_bills')->pluck('properties.id');

        $bills = Bill::whereIn('property_id', $propertyIds)
            ->with('property')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->whereHas('items', fn ($i) => $i->where('charge_type', $request->string('type'))))
            ->orderBy($request->string('sort', 'issue_date')->toString() === 'due_date' ? 'due_date' : 'issue_date', 'desc')
            ->paginate(15)->withQueryString();

        return view('bills.index', compact('bills'));
    }

    public function show(Bill $bill): View
    {
        $this->authorize('view', $bill);

        $bill->load('items', 'property', 'account');

        return view('bills.show', compact('bill'));
    }
}
