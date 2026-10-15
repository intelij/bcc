<x-admin-layout>
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-xl font-semibold">Disconnections</h1>
        <a href="{{ route('admin.disconnections.create') }}" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-medium text-white">Schedule disconnection</a>
    </div>

    <form method="GET" class="mb-4 flex gap-3">
        <select name="status" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ status_label($s) }}</option>@endforeach
        </select>
        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">Filter</button>
    </form>

    <div class="space-y-3">
        @forelse ($notices as $n)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="font-medium">{{ $n->account->account_number }} — {{ $n->property->fullAddress() }}</div>
                        <div class="text-sm text-slate-500">Arrears {{ money($n->arrears_cents) }} · Reason: {{ $n->reason ?? '—' }}</div>
                        <div class="text-xs text-slate-400 mt-1">Notice {{ $n->notice_date?->toFormattedDateString() }} · Scheduled {{ $n->scheduled_date?->toFormattedDateString() ?? '—' }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-status-badge :status="$n->status" />
                        <form method="POST" action="{{ route('admin.disconnections.status', $n) }}" class="flex gap-2">
                            @csrf @method('PATCH')
                            <select name="status" class="rounded-lg border-slate-300 text-xs">
                                @foreach ($statuses as $s)<option value="{{ $s }}" @selected($n->status === $s)>{{ status_label($s) }}</option>@endforeach
                            </select>
                            <button class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs text-white">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <x-empty-state title="No disconnection notices" />
        @endforelse
    </div>
    <div class="mt-4">{{ $notices->links() }}</div>
</x-admin-layout>
