<x-admin-layout>
    <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold">Account {{ $account->account_number }}</h1>
            <div class="text-sm text-slate-500">{{ $account->holder_name }} · {{ $account->property->fullAddress() }}</div>
        </div>
        <div class="text-right">
            <div class="text-sm text-slate-500">Balance</div>
            <div class="text-2xl font-semibold">{{ money($account->balance_cents) }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Bills</h2>
            @forelse ($account->bills as $bill)
                <a href="{{ route('admin.billing.show', $bill) }}" class="flex justify-between py-2 text-sm border-b border-slate-100 last:border-0">
                    <span>{{ $bill->invoice_number }} <span class="text-slate-400">{{ $bill->issue_date->format('M Y') }}</span></span>
                    <span class="flex gap-2 items-center">{{ money($bill->total_cents) }} <x-status-badge :status="$bill->status" /></span>
                </a>
            @empty
                <p class="text-sm text-slate-500">No bills.</p>
            @endforelse
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Payments</h2>
            @forelse ($account->payments->take(10) as $p)
                <a href="{{ route('admin.payments.show', $p) }}" class="flex justify-between py-2 text-sm border-b border-slate-100 last:border-0">
                    <span class="font-mono text-xs">{{ Str::limit($p->uuid, 18) }}</span>
                    <span class="flex gap-2 items-center">{{ money($p->amount_cents) }} <x-status-badge :status="$p->status" /></span>
                </a>
            @empty
                <p class="text-sm text-slate-500">No payments.</p>
            @endforelse
        </div>
    </div>
</x-admin-layout>
