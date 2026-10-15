<x-app-layout>
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-xl font-semibold">Statement — {{ $account->account_number }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('statements.pdf', ['account' => $account->id, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="rounded-lg bg-[#143E62] px-4 py-2 text-sm font-medium text-white">Download PDF</a>
            <form method="POST" action="{{ route('statements.email', $account) }}">@csrf
                <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Email me</button>
            </form>
        </div>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-3">
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border-slate-300 text-sm">
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border-slate-300 text-sm">
        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">Apply</button>
    </form>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <div class="px-4 py-3 text-sm border-b border-slate-200 flex justify-between">
            <span class="text-slate-500">Opening balance ({{ $from->toFormattedDateString() }})</span>
            <span class="font-medium">{{ money($opening) }}</span>
        </div>
        <table class="w-full text-sm min-w-[560px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Description</th><th class="px-4 py-3">Type</th><th class="px-4 py-3 text-right">Amount</th></tr>
            </thead>
            <tbody>
                @forelse ($lines as $line)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="px-4 py-2.5 whitespace-nowrap">{{ $line->date->toFormattedDateString() }}</td>
                        <td class="px-4 py-2.5">{{ $line->description }}</td>
                        <td class="px-4 py-2.5 text-slate-500">{{ ucfirst($line->kind) }}</td>
                        <td class="px-4 py-2.5 text-right {{ $line->amount_cents < 0 ? 'text-emerald-700' : '' }}">{{ money($line->amount_cents) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No activity in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-slate-200 flex justify-between font-semibold">
            <span>Closing balance</span><span>{{ money($closing) }}</span>
        </div>
    </div>
</x-app-layout>
