<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestComment;
use App\Models\User;
use App\Services\ServiceRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = ServiceRequest::with('user', 'property', 'assignee')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.requests.index', [
            'requests' => $requests,
            'statuses' => ServiceRequest::STATUSES,
            'categories' => ServiceRequest::CATEGORIES,
        ]);
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        $serviceRequest->load('user', 'property', 'comments.user', 'attachments', 'assignee');

        return view('admin.requests.show', [
            'request' => $serviceRequest,
            'statuses' => ServiceRequest::STATUSES,
            'officers' => User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['customer_service', 'admin']))->get(),
        ]);
    }

    public function update(Request $request, ServiceRequest $serviceRequest, ServiceRequestService $service): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', ServiceRequest::STATUSES),
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $service->updateStatus($serviceRequest, $data['status'],
            isset($data['assigned_to']) ? User::find($data['assigned_to']) : null);

        return back()->with('success', 'Request updated.');
    }

    public function comment(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
            'is_internal' => 'boolean',
        ]);

        ServiceRequestComment::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'is_internal' => $request->boolean('is_internal'),
        ]);

        return back()->with('success', 'Comment added.');
    }
}
