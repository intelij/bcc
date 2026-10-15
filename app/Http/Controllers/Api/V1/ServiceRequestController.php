<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Property;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestComment;
use App\Services\ServiceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceRequestController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $requests = $request->user()->serviceRequests()
            ->with('attachments')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(15)->withQueryString();

        $requests->setCollection($requests->getCollection()->map(fn ($r) => $this->requestData($r)));

        return response()->json($requests);
    }

    /** Create a service request; `photos[]` multipart files are optional. */
    public function store(Request $request, ServiceRequestService $service): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', 'in:'.implode(',', array_keys(ServiceRequest::CATEGORIES))],
            'description' => ['required', 'string', 'max:5000'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'photos.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:4096'],
        ]);

        if (! empty($data['property_id'])) {
            $this->authorize('submitRequests', Property::findOrFail($data['property_id']));
        }

        $ticket = $service->create($request->user(), $data, $request->file('photos', []));

        return response()->json([
            'data' => $this->requestData($ticket->loadMissing('attachments')),
            'message' => 'Service request '.$ticket->ticket_number.' submitted.',
        ], 201);
    }

    public function show(ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('view', $serviceRequest);
        $serviceRequest->load('publicComments.user', 'attachments');

        return response()->json(['data' => $this->requestData($serviceRequest)]);
    }

    public function comment(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('comment', $serviceRequest);

        $data = $request->validate(['body' => 'required|string|max:5000']);

        $comment = ServiceRequestComment::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'is_internal' => false,
        ]);

        return response()->json([
            'data' => [
                'author' => $request->user()->name,
                'staff' => false,
                'body' => $comment->body,
                'created_at' => $comment->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
