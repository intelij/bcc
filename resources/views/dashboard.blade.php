<x-app-layout>
    {{-- Property selector --}}
    @if ($properties->isNotEmpty())
        <form method="GET" class="mb-5 flex items-center gap-3">
            <label class="text-sm text-slate-500">Property</label>
            <select name="property" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm">
                @foreach ($properties as $p)
                    <option value="{{ $p->id }}" @selected($selected?->id === $p->id)>{{ $p->fullAddress() }} — {{ $p->account?->account_number }}</option>
                @endforeach
            </select>
        </form>
    @else
        <x-empty-state title="No linked property" message="Link your municipal account to see bills, balances and services.">
            <a href="{{ route('properties.create') }}" class="mt-3 inline-block rounded-lg bg-[#143E62] px-4 py-2 text-sm font-medium text-white">Link a property</a>
        </x-empty-state>
    @endif

    @if ($disconnection)
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            <strong>Disconnection {{ status_label($disconnection->status) }}.</strong>
            Arrears of {{ money($disconnection->arrears_cents) }}
            @if ($disconnection->scheduled_date)— scheduled for {{ $disconnection->scheduled_date->toFormattedDateString() }}@endif.
            Pay now or contact the council.
        </div>
    @endif

    @foreach ($interruptions as $i)
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>{{ $i->title }}</strong> — {{ $i->area }}@if($i->suburb), {{ $i->suburb }}@endif.
            From {{ $i->starts_at->toFormattedDateString() }}@if($i->expected_restoration_at), expected restoration {{ $i->expected_restoration_at->toFormattedDateString() }}@endif.
        </div>
    @endforeach

    @if ($account)
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            <x-stat-card label="Total outstanding" :value="money($outstanding)" :tone="$outstanding > 0 ? 'danger' : 'success'" />
            <x-stat-card label="Current rates" :value="money($currentRates)" />
            <x-stat-card label="Current water" :value="money($currentWater)" />
            <x-stat-card label="Overdue" :value="money($overdue)" :tone="$overdue > 0 ? 'danger' : 'default'" />
        </div>
        <div class="grid grid-cols-2 gap-3 mb-6">
            <x-stat-card label="Next due date" :value="$nextDue?->toFormattedDateString() ?? '—'" />
            <x-stat-card label="Last payment" :value="$lastPayment ? money($lastPayment->amount_cents) : '—'" :sub="$lastPayment?->paid_at?->toFormattedDateString()" />
        </div>

        <a href="{{ route('payments.create', ['account' => $account->id]) }}" class="mb-6 inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-[#143E62] px-8 py-3 text-base font-semibold text-white shadow hover:bg-[#0f2f4a]">Pay now</a>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold">Recent bills</h2>
                    <a href="{{ route('bills.index') }}" class="text-sm text-[#285D9A] hover:underline">View all</a>
                </div>
                @forelse ($bills as $bill)
                    <a href="{{ route('bills.show', $bill) }}" class="flex items-center justify-between py-2.5 border-b border-slate-100 last:border-0 text-sm hover:bg-slate-50 -mx-2 px-2 rounded">
                        <div>
                            <div class="font-medium">{{ $bill->invoice_number }}</div>
                            <div class="text-slate-500 text-xs">Due {{ $bill->due_date->toFormattedDateString() }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-medium">{{ money($bill->outstandingCents()) }}</div>
                            <x-status-badge :status="$bill->status" />
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-500 py-4">No bills yet.</p>
                @endforelse
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold">Open service requests</h2>
                    <a href="{{ route('requests.create') }}" class="text-sm text-[#285D9A] hover:underline">Report a problem</a>
                </div>
                @forelse ($openRequests as $r)
                    <a href="{{ route('requests.show', $r) }}" class="flex items-center justify-between py-2.5 border-b border-slate-100 last:border-0 text-sm hover:bg-slate-50 -mx-2 px-2 rounded">
                        <div>
                            <div class="font-medium">{{ $r->ticket_number }}</div>
                            <div class="text-slate-500 text-xs">{{ $r->categoryLabel() }}</div>
                        </div>
                        <x-status-badge :status="$r->status" />
                    </a>
                @empty
                    <p class="text-sm text-slate-500 py-4">No open requests.</p>
                @endforelse
            </div>
        </div>
    @endif
</x-app-layout>
