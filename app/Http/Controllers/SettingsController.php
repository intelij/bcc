<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        $prefs = $user->notificationPreferences->groupBy('event');

        return view('settings.notifications', [
            'events' => NotificationService::EVENTS,
            'channels' => NotificationService::CHANNELS,
            'prefs' => $prefs,
            'service' => app(NotificationService::class),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['prefs' => ['array'], 'prefs.*' => ['boolean']]);

        foreach (NotificationService::EVENTS as $event) {
            foreach (NotificationService::CHANNELS as $channel) {
                NotificationPreference::updateOrCreate(
                    ['user_id' => $user->id, 'event' => $event, 'channel' => $channel],
                    ['enabled' => (bool) ($data['prefs']["$event.$channel"] ?? false)]
                );
            }
        }

        return back()->with('success', 'Notification preferences saved.');
    }
}
