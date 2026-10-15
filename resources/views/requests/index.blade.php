<x-app-layout>
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold">Service Requests</h1>
        <a href="{{ route('requests.create') }}" class="rounded-lg bg-[#143E62] px-4 py-2 text-sm font-medium text-white">Report a problem</a>
    </div>

    <div class="space-y-2">
        @forelse ($requests as $r)
            <a href="{{ route('requests.show', $r) }}" class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-[#5691AD]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium text-sm">{{ $r->ticket_number }} — {{ $r->categoryLabel() }}</div>
                        <p class="text-sm text-slate-600 mt-0.5 line-clamp-1">{{ $r->description }}</p>
                        <div class="text-xs text-slate-400 mt-1">{{ $r->created_at->diffForHumans() }}@if($r->assignee) · Assigned to {{ $r->assignee->name }}@endif</div>
                    </div>
                    <x-status-badge :status="$r->status" />
                </div>
            </a>
        @empty
            <x-empty-state title="No service requests" message="Report a water leak, missed refuse collection, road damage and more." />
        @endforelse
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-app-layout>
