<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Properties</h1>

    @if ($pendingLinks->isNotEmpty())
        <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-4">
            <h2 class="font-semibold text-sm mb-3">Pending link requests ({{ $pendingLinks->count() }})</h2>
            @foreach ($pendingLinks as $link)
                <div class="flex flex-wrap items-center gap-3 py-2 border-b border-amber-200 last:border-0 text-sm">
                    <div class="flex-1">
                        <span class="font-medium">{{ $link->user->name }}</span> ({{ $link->user->email }})
                        → {{ $link->property->fullAddress() }} <span class="text-slate-500">as {{ $link->relationship }}</span>
                    </div>
                    <form method="POST" action="{{ route('admin.links.review', $link) }}" class="flex gap-2">
                        @csrf
                        <button name="action" value="approve" class="rounded-lg bg-[#285D9A] px-3 py-1.5 text-xs font-medium text-white">Approve</button>
                        <button name="action" value="reject" class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700">Reject</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Address, suburb or reference" class="rounded-lg border-slate-300 text-sm">
        <select name="status" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach (App\Models\Property::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ status_label($s) }}</option>@endforeach
        </select>
        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">Filter</button>
    </form>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[700px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Address</th><th class="px-4 py-3">Ward</th><th class="px-4 py-3">Account</th><th class="px-4 py-3 text-right">Balance</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody>
                @forelse ($properties as $p)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                        <td class="px-4 py-3"><a href="{{ route('admin.properties.show', $p) }}" class="font-medium text-[#285D9A] hover:underline">{{ $p->reference }}</a></td>
                        <td class="px-4 py-3">{{ $p->address_line }}, {{ $p->suburb }}</td>
                        <td class="px-4 py-3">{{ $p->ward }}</td>
                        <td class="px-4 py-3">{{ $p->account?->account_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ $p->account ? money($p->account->balance_cents) : '—' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$p->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No properties found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $properties->links() }}</div>
</x-admin-layout>
