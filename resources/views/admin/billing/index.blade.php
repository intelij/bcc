<x-admin-layout>
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-xl font-semibold">Billing</h1>
        <a href="{{ route('admin.billing.create') }}" class="rounded-lg bg-[#285D9A] px-4 py-2 text-sm font-medium text-white">Issue bill</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Invoice or account" class="rounded-lg border-slate-300 text-sm">
        <select name="status" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ status_label($s) }}</option>@endforeach
        </select>
        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">Filter</button>
    </form>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[760px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr><th class="px-4 py-3">Invoice</th><th class="px-4 py-3">Account</th><th class="px-4 py-3">Period</th><th class="px-4 py-3">Due</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">Paid</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody>
                @forelse ($bills as $b)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                        <td class="px-4 py-3"><a href="{{ route('admin.billing.show', $b) }}" class="font-medium text-[#285D9A] hover:underline">{{ $b->invoice_number }}</a></td>
                        <td class="px-4 py-3">{{ $b->account->account_number }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $b->period_start->format('M Y') }}</td>
                        <td class="px-4 py-3">{{ $b->due_date->toFormattedDateString() }}</td>
                        <td class="px-4 py-3 text-right">{{ money($b->total_cents) }}</td>
                        <td class="px-4 py-3 text-right">{{ money($b->amount_paid_cents) }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$b->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No bills found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $bills->links() }}</div>
</x-admin-layout>
