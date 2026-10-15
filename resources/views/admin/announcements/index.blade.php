<x-admin-layout>
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold">Announcements</h1>
        <a href="{{ route('admin.announcements.create') }}" class="rounded-lg bg-[#285D9A] px-4 py-2 text-sm font-medium text-white">New announcement</a>
    </div>

    <div class="space-y-2">
        @forelse ($announcements as $a)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-48">
                    <div class="font-medium">{{ $a->title }}</div>
                    <div class="text-xs text-slate-500">{{ status_label($a->category) }} · {{ $a->priority }} priority · {{ $a->published_at?->toFormattedDateString() ?? 'not published' }}</div>
                </div>
                <x-status-badge :status="$a->status" />
                <div class="flex gap-2">
                    <a href="{{ route('admin.announcements.edit', $a) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs">Edit</a>
                    @if ($a->status !== 'published')
                        <form method="POST" action="{{ route('admin.announcements.publish', $a) }}">@csrf
                            <button class="rounded-lg bg-[#285D9A] px-3 py-1.5 text-xs font-medium text-white">Publish</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')
                        <button class="rounded-lg border border-red-300 px-3 py-1.5 text-xs text-red-700">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <x-empty-state title="No announcements" />
        @endforelse
    </div>
    <div class="mt-4">{{ $announcements->links() }}</div>
</x-admin-layout>
