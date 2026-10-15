<x-admin-layout>
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-xl font-semibold">Payments</h1>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="UUID / reference / provider ref" class="rounded-lg border-slate-300 text-sm">
        <select name="status" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ status_label($s) }}</option>@endforeach
        </select>
        <input name="provider" value="{{ request('provider') }}" placeholder="Provider" class="rounded-lg border-slate-300 text-sm w-32">
        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">Filter</button>
    </form>

    @can('payments.reconcile', App\Models\Payment::class)
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Record manual payment (cash/counter)</h2>
            <form method="POST" action="{{ route('admin.payments.manual') }}" class="grid sm:grid-cols-4 gap-3 items-end">
                @csrf
                <div class="sm:col-span-2">
                    <x-input-label value="Account" />
                    <select name="municipal_account_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                        @foreach (App\Models\MunicipalAccount::orderBy('account_number')->get() as $a)
                            <option value="{{ $a->id }}">{{ $a->account_number }} — {{ $a->holder_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><x-input-label value="Amount (USD)" /><input type="number" step="0.01" min="0.01" name="amount" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></div>
                <div><x-input-label value="Reference" /><input name="reference" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></div>
                <x-primary-button class="sm:col-span-4 w-fit">Record & allocate</x-primary-button>
            </form>
        </div>
    @endcan

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[800px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Account</th><th class="px-4 py-3">Payer</th><th class="px-4 py-3">Provider</th><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody>
                @forelse ($payments as $p)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                        <td class="px-4 py-3"><a href="{{ route('admin.payments.show', $p) }}" class="font-mono text-xs text-[#285D9A] hover:underline">{{ Str::limit($p->uuid, 16) }}</a></td>
                        <td class="px-4 py-3">{{ $p->account?->account_number ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $p->user?->name ?? ($p->meta['payer'] ?? 'import/manual') }}</td>
                        <td class="px-4 py-3">{{ $p->provider }}</td>
                        <td class="px-4 py-3">{{ $p->created_at->format('d M H:i') }}</td>
                        <td class="px-4 py-3 text-right">{{ money($p->amount_cents) }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$p->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
</x-admin-layout>
