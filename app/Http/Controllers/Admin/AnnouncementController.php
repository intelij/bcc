<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AuditService $audit,
        protected NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $announcements = Announcement::with('creator')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.announcements.index', [
            'announcements' => $announcements,
            'statuses' => Announcement::STATUSES,
            'categories' => Announcement::CATEGORIES,
        ]);
    }

    public function create(): View
    {
        return view('admin.announcements.create', ['categories' => Announcement::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $announcement = Announcement::create($data + [
            'slug' => Str::slug($data['title']).'-'.Str::random(6),
            'status' => Announcement::STATUS_DRAFT,
            'created_by' => $request->user()->id,
        ]);

        $this->syncTargets($announcement, $request->string('targets_suburbs')->toString());

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement drafted.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.edit', [
            'announcement' => $announcement->load('targets'),
            'categories' => Announcement::CATEGORIES,
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));
        $this->syncTargets($announcement, $request->string('targets_suburbs')->toString());

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function publish(Announcement $announcement): RedirectResponse
    {
        $old = $announcement->only('status');
        $announcement->forceFill([
            'status' => Announcement::STATUS_PUBLISHED,
            'published_at' => now(),
        ])->save();

        $this->audit->record('announcement.published', $announcement, $old, $announcement->only('status'));

        $this->notifications->notify(User::where('status', 'active')->get()->all(), 'CITY_ANNOUNCEMENT', [
            'title' => $announcement->title,
            'message' => Str::limit(strip_tags($announcement->body), 200),
            'data' => ['announcement' => $announcement->slug],
        ]);

        return back()->with('success', 'Announcement published and residents notified.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category' => 'required|in:'.implode(',', Announcement::CATEGORIES),
            'priority' => 'required|in:low,normal,high,urgent',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);
    }

    protected function syncTargets(Announcement $announcement, string $suburbs): void
    {
        $announcement->targets()->delete();
        foreach (array_filter(array_map('trim', explode(',', $suburbs))) as $suburb) {
            $announcement->targets()->create(['suburb' => $suburb]);
        }
    }
}
