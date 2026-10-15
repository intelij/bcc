<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $announcements = Announcement::published()
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->latest('published_at')->paginate(10)->withQueryString();

        return view('announcements.index', [
            'announcements' => $announcements,
            'categories' => Announcement::CATEGORIES,
        ]);
    }

    public function show(Announcement $announcement): View
    {
        abort_unless($announcement->status === Announcement::STATUS_PUBLISHED || request()->user()?->isStaff(), 404);

        return view('announcements.show', compact('announcement'));
    }
}
