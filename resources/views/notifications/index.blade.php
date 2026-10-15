<x-app-layout>
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold">Notifications</h1>
        <form method="POST" action="{{ route('notifications.readAll') }}">@csrf
            <button class="text-sm text-[#285D9A] hover:underline">Mark all read</button>
        </form>
    </div>

    <div class="space-y-2">
        @forelse ($notifications as $n)
            <div class="rounded-xl border {{ $n->read_at ? 'border-slate-200 bg-white' : 'border-sky-300 bg-sky-50/40' }} p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium text-sm">{{ $n->title }}</div>
                        <p class="text-sm text-slate-600 mt-0.5">{{ $n->body }}</p>
                        <div class="text-xs text-slate-400 mt-1">{{ $n->created_at->diffForHumans() }} · {{ $n->event }}</div>
                    </div>
                    @unless ($n->read_at)
                        <form method="POST" action="{{ route('notifications.read', $n) }}">@csrf
                            <button class="text-xs text-[#285D9A] hover:underline shrink-0">Mark read</button>
                        </form>
                    @endunless
                </div>
            </div>
        @empty
            <x-empty-state title="No notifications" />
        @endforelse
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
</x-app-layout>
