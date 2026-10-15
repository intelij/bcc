<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Service requests</h1>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <select name="status" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ status_label($s) }}</option>@endforeach
        </select>
        <select name="category" class="rounded-lg border-slate-300 text-sm">
            <option value="">All categories</option>
            @foreach ($categories as $k => $v)<option value="{{ $k }}" @selected(request('category') === $k)>{{ $v }}</option>@endforeach
        </select>
        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">Filter</button>
    </form>

    <div class="space-y-2">
        @forelse ($requests as $r)
            <a href="{{ route('admin.requests.show', $r) }}" class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-[#5691AD]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium text-sm">{{ $r->ticket_number }} — {{ $r->categoryLabel() }}</div>
                        <div class="text-sm text-slate-600">{{ $r->user->name }} · {{ Str::limit($r->description, 90) }}</div>
                        <div class="text-xs text-slate-400 mt-1">{{ $r->created_at->diffForHumans() }}@if($r->property) · {{ $r->property->suburb }}@endif @if($r->assignee)· → {{ $r->assignee->name }}@endif</div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <x-status-badge :status="$r->status" />
                        @if ($r->priority === 'urgent' || $r->priority === 'high')<span class="text-xs text-red-600 font-medium">{{ ucfirst($r->priority) }}</span>@endif
                    </div>
                </div>
            </a>
        @empty
            <x-empty-state title="No service requests" />
        @endforelse
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-admin-layout>
