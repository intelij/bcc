<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Bill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $propertyIds = $request->user()
            ->accessibleProperties('can_view_bills')->pluck('properties.id');

        $bills = Bill::whereIn('property_id', $propertyIds)
            ->with('account')
            ->when($request->filled('account_id'), fn ($q) => $q->where('municipal_account_id', $request->integer('account_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('issue_date')
            ->paginate(20)->withQueryString();

        $bills->setCollection($bills->getCollection()->map(fn ($b) => $this->billData($b)));

        return response()->json($bills);
    }

    public function show(Bill $bill): JsonResponse
    {
        $this->authorize('view', $bill);
        $bill->load('items', 'account');

        return response()->json(['data' => $this->billData($bill)]);
    }
}
