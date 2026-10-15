<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/** Municipal service request (ticket) lifecycle. */
class ServiceRequestService
{
    public function __construct(
        protected NotificationService $notifications,
        protected AuditService $audit,
    ) {}

    public function create(User $user, array $data, array $photos = []): ServiceRequest
    {
        return DB::transaction(function () use ($user, $data, $photos) {
            $request = ServiceRequest::create([
                'ticket_number' => $this->nextTicketNumber(),
                'user_id' => $user->id,
                'property_id' => $data['property_id'] ?? null,
                'category' => $data['category'],
                'description' => $data['description'],
                'location_text' => $data['location_text'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'status' => ServiceRequest::STATUS_SUBMITTED,
            ]);

            foreach ($photos as $photo) {
                $this->storeAttachment($request, $photo, $user);
            }

            $this->notifications->notifyUser($user, 'SERVICE_REQUEST_CREATED', [
                'title' => 'Service request received',
                'message' => 'Ticket '.$request->ticket_number.' ('.$request->categoryLabel().') has been logged.',
                'data' => ['ticket' => $request->ticket_number],
            ]);

            $this->audit->record('service_request.created', $request);

            return $request;
        });
    }

    public function updateStatus(ServiceRequest $request, string $status, ?User $assignee = null): ServiceRequest
    {
        $old = $request->only('status', 'assigned_to');

        $request->forceFill(array_filter([
            'status' => $status,
            'assigned_to' => $assignee?->id ?? $request->assigned_to,
            'acknowledged_at' => $status === ServiceRequest::STATUS_ACKNOWLEDGED ? now() : $request->acknowledged_at,
            'resolved_at' => $status === ServiceRequest::STATUS_RESOLVED ? now() : $request->resolved_at,
            'closed_at' => $status === ServiceRequest::STATUS_CLOSED ? now() : $request->closed_at,
        ], fn ($v) => $v !== null))->save();

        $this->audit->record('service_request.status_changed', $request, $old, $request->only('status', 'assigned_to'));

        $this->notifications->notifyUser($request->user, 'SERVICE_REQUEST_UPDATED', [
            'title' => 'Service request updated',
            'message' => 'Ticket '.$request->ticket_number.' is now '.status_label($status).'.',
            'data' => ['ticket' => $request->ticket_number, 'status' => $status],
        ]);

        return $request->fresh();
    }

    public function storeAttachment(ServiceRequest $request, UploadedFile $file, User $user): void
    {
        $path = $file->store('service-requests/'.$request->id, 'private');

        $request->attachments()->create([
            'uploaded_by' => $user->id,
            'disk' => 'private',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    protected function nextTicketNumber(): string
    {
        return 'SR-'.now()->format('Ymd').'-'.str_pad((string) (ServiceRequest::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
