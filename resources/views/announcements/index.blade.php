<x-app-layout>
    <h1 class="text-xl font-semibold mb-5">City Updates</h1>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('announcements.index') }}" class="rounded-full px-3 py-1 border {{ ! request('category') ? 'bg-[#143E62] text-white border-[#143E62]' : 'border-slate-300' }}">All</a>
        @foreach ($categories as $c)
            <a href="{{ route('announcements.index', ['category' => $c]) }}" class="rounded-full px-3 py-1 border {{ request('category') === $c ? 'bg-[#143E62] text-white border-[#143E62]' : 'border-slate-300' }}">{{ status_label($c) }}</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($announcements as $a)
            <a href="{{ route('announcements.show', $a) }}" class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-[#5691AD]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium">{{ $a->title }}</div>
                        <p class="text-sm text-slate-600 mt-1 line-clamp-2">{{ Str::limit(strip_tags($a->body), 160) }}</p>
                        <div class="text-xs text-slate-400 mt-2">{{ status_label($a->category) }} · {{ $a->published_at?->diffForHumans() }}</div>
                    </div>
                    @if ($a->priority === 'urgent')
                        <span class="shrink-0 rounded-full bg-red-100 text-red-700 px-2 py-0.5 text-xs font-medium">Urgent</span>
                    @endif
                </div>
            </a>
        @empty
            <x-empty-state title="No updates" />
        @endforelse
    </div>
    <div class="mt-4">{{ $announcements->links() }}</div>
</x-app-layout>
