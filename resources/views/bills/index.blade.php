<x-app-layout>
    <h1 class="text-xl font-semibold mb-5">My Bills</h1>

    <form method="GET" class="mb-4 flex flex-wrap gap-3 items-center">
        <select name="status" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach (['issued','partially_paid','paid','overdue','disputed','cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ status_label($s) }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-lg border-slate-300 text-sm">
            <option value="">All charges</option>
            @foreach (['rates','water','refuse','sewer','other'] as $t)
                <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">Filter</button>
    </form>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3">Invoice</th><th class="px-4 py-3">Property</th><th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3"><a href="?sort=due_date">Due</a></th><th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Outstanding</th><th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bills as $bill)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                        <td class="px-4 py-3"><a href="{{ route('bills.show', $bill) }}" class="font-medium text-[#285D9A] hover:underline">{{ $bill->invoice_number }}</a></td>
                        <td class="px-4 py-3 text-slate-600">{{ $bill->property->suburb }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $bill->period_start->format('M Y') }}</td>
                        <td class="px-4 py-3">{{ $bill->due_date->toFormattedDateString() }}</td>
                        <td class="px-4 py-3 text-right">{{ money($bill->total_cents) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ money($bill->outstandingCents()) }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$bill->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No bills found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $bills->links() }}</div>
</x-app-layout>
