<x-admin-layout>
    <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold">{{ $bill->invoice_number }}</h1>
            <div class="text-sm text-slate-500">
                <a class="text-[#285D9A] hover:underline" href="{{ route('admin.accounts.show', $bill->account) }}">{{ $bill->account->account_number }}</a>
                · {{ $bill->property->fullAddress() }} · {{ $bill->period_start->format('M Y') }}
            </div>
        </div>
        <x-status-badge :status="$bill->status" />
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                    <tr><th class="px-4 py-3">Charge</th><th class="px-4 py-3">Type</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Amount</th></tr>
                </thead>
                <tbody>
                    @foreach ($bill->items as $item)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="px-4 py-3">{{ $item->description }}@if($item->meter_reading_id) <span class="text-xs text-slate-400">(metered)</span>@endif</td>
                            <td class="px-4 py-3 text-slate-500">{{ ucfirst($item->charge_type) }}</td>
                            <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right">{{ money($item->amount_cents) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <dl class="px-4 py-4 border-t border-slate-200 space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Previous balance</dt><dd>{{ money($bill->previous_balance_cents) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Charges</dt><dd>{{ money($bill->charges_cents) }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>Total</dt><dd>{{ money($bill->total_cents) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd>{{ money($bill->amount_paid_cents) }}</dd></div>
            </dl>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="font-semibold text-sm mb-3">Actions</h2>
                <form method="POST" action="{{ route('admin.billing.status', $bill) }}" class="flex gap-2">
                    @csrf @method('PATCH')
                    <select name="status" class="rounded-lg border-slate-300 text-sm">
                        @foreach (['issued','disputed','cancelled'] as $s)<option value="{{ $s }}">{{ status_label($s) }}</option>@endforeach
                    </select>
                    <x-primary-button>Set status</x-primary-button>
                </form>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="font-semibold text-sm mb-3">Payment allocations</h2>
                @forelse ($bill->allocations as $a)
                    <div class="py-1.5 text-sm flex justify-between border-b border-slate-100 last:border-0">
                        <a href="{{ route('admin.payments.show', $a->payment) }}" class="font-mono text-xs text-[#285D9A]">{{ Str::limit($a->payment->uuid, 18) }}</a>
                        <span>{{ money($a->amount_cents) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No payments allocated.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
