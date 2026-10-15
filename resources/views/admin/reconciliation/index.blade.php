<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Reconciliation</h1>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="px-4 py-3 border-b border-slate-200 font-semibold text-sm">Unallocated / partially allocated payments</div>
            <div class="divide-y divide-slate-100">
                @forelse ($unallocated as $p)
                    <div class="px-4 py-3 text-sm flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-48">
                            <div class="font-mono text-xs">{{ $p->uuid }}</div>
                            <div class="text-slate-500">Ref: {{ $p->reference ?? '—' }} · {{ $p->created_at->toFormattedDateString() }}</div>
                        </div>
                        <div class="font-medium">{{ money($p->amount_cents) }}</div>
                        <x-status-badge :status="$p->status" />
                        <form method="POST" action="{{ route('admin.reconciliation.allocate', $p) }}" class="flex gap-2">
                            @csrf
                            <select name="municipal_account_id" class="rounded-lg border-slate-300 text-xs">
                                @foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->account_number }}</option>@endforeach
                            </select>
                            <button class="rounded-lg bg-[#285D9A] px-3 py-1.5 text-xs font-medium text-white">Allocate</button>
                        </form>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-slate-500">All payments allocated.</div>
                @endforelse
            </div>
            <div class="px-4 py-3 border-t border-slate-200">{{ $unallocated->links() }}</div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="font-semibold text-sm mb-2">Import bank CSV</h2>
                <p class="text-xs text-slate-500 mb-3">Columns: date, reference, amount, account_number (optional), payer (optional).</p>
                <form method="POST" action="{{ route('admin.reconciliation.import') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="file" accept=".csv,.txt" required class="block w-full text-sm">
                    <x-primary-button>Import</x-primary-button>
                </form>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="px-4 py-3 border-b border-slate-200 font-semibold text-sm">Recent imports</div>
                <div class="divide-y divide-slate-100 text-sm">
                    @forelse ($imports as $i)
                        <div class="px-4 py-3">
                            <div class="font-medium">{{ $i->filename }}</div>
                            <div class="text-xs text-slate-500">{{ $i->importer->name }} · {{ $i->created_at->diffForHumans() }} · {{ $i->rows_matched }}/{{ $i->rows_total }} matched</div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-slate-500 text-sm">No imports yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
