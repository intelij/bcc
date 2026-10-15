<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestComment;
use App\Services\ServiceRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = $request->user()->serviceRequests()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(15)->withQueryString();

        return view('requests.index', [
            'requests' => $requests,
            'statuses' => ServiceRequest::STATUSES,
        ]);
    }

    public function create(Request $request): View
    {
        return view('requests.create', [
            'categories' => ServiceRequest::CATEGORIES,
            'properties' => $request->user()->accessibleProperties('can_submit_requests')->get(),
        ]);
    }

    public function store(Request $request, ServiceRequestService $service): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'in:'.implode(',', array_keys(ServiceRequest::CATEGORIES))],
            'description' => ['required', 'string', 'max:5000'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'photos.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:4096'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
        ]);

        if (! empty($data['property_id'])) {
            $property = Property::findOrFail($data['property_id']);
            $this->authorize('submitRequests', $property);
        }

        $ticket = $service->create($request->user(), $data, $request->file('photos', []));

        return redirect()->route('requests.show', $ticket)
            ->with('success', 'Service request '.$ticket->ticket_number.' submitted.');
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        $this->authorize('view', $serviceRequest);
        $serviceRequest->load('publicComments.user', 'attachments', 'property', 'assignee');

        return view('requests.show', ['request' => $serviceRequest]);
    }

    public function comment(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('comment', $serviceRequest);

        $data = $request->validate(['body' => 'required|string|max:5000']);

        ServiceRequestComment::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'is_internal' => false,
        ]);

        return back()->with('success', 'Comment added.');
    }
}
