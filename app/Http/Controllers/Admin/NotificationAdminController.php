<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationDelivery;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationAdminController extends Controller
{
    public function index(Request $request): View
    {
        $deliveries = NotificationDelivery::with('user')
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->string('channel')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.notifications.index', [
            'deliveries' => $deliveries,
            'templates' => NotificationTemplate::orderBy('event')->orderBy('channel')->get(),
            'events' => NotificationService::EVENTS,
        ]);
    }

    /** Ad-hoc targeted notification to a single user. */
    public function send(Request $request, NotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'event' => 'required|in:'.implode(',', NotificationService::EVENTS),
            'message' => 'required|string|max:1000',
        ]);

        $notifications->notifyUser(User::findOrFail($data['user_id']), $data['event'], [
            'title' => 'Message from '.config('city.short_name'),
            'message' => $data['message'],
        ]);

        return back()->with('success', 'Notification sent.');
    }

    public function updateTemplate(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'subject' => 'nullable|string|max:255',
            'body_template' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Template updated.');
    }
}
