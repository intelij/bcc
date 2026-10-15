<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->paginate(20)->withQueryString();

        $notifications->setCollection($notifications->getCollection()->map(fn ($n) => $this->notificationData($n)));

        return response()->json($notifications);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->markRead();

        return response()->json(['data' => $this->notificationData($notification->fresh())]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /** Notification channel preferences per event. */
    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $stored = $user->notificationPreferences()->get()
            ->mapWithKeys(fn ($p) => [$p->event.'.'.$p->channel => $p->enabled]);

        return response()->json([
            'events' => NotificationService::EVENTS,
            'channels' => NotificationService::CHANNELS,
            'data' => collect(NotificationService::EVENTS)->mapWithKeys(fn ($event) => [
                $event => collect(NotificationService::CHANNELS)->mapWithKeys(
                    fn ($channel) => [$channel => $stored["$event.$channel"] ?? null]
                ),
            ]),
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.*' => ['boolean'],
        ]);

        $user = $request->user();

        foreach ($data['preferences'] as $event => $channels) {
            abort_unless(in_array($event, NotificationService::EVENTS), 422, "Unknown event: $event");

            foreach ($channels as $channel => $enabled) {
                abort_unless(in_array($channel, NotificationService::CHANNELS), 422, "Unknown channel: $channel");

                $user->notificationPreferences()->updateOrCreate(
                    ['event' => $event, 'channel' => $channel],
                    ['enabled' => (bool) $enabled]
                );
            }
        }

        return $this->preferences($request);
    }
}
