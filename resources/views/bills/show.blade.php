<x-app-layout>
    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-semibold">{{ $bill->invoice_number }}</h1>
            <div class="text-sm text-slate-500">
                {{ $bill->property->fullAddress() }} · Acct {{ $bill->account->account_number }}
                · Period {{ $bill->period_start->format('M Y') }} · Due {{ $bill->due_date->toFormattedDateString() }}
            </div>
        </div>
        <x-status-badge :status="$bill->status" />
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr><th class="px-4 py-3">Charge</th><th class="px-4 py-3">Type</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Amount</th></tr>
            </thead>
            <tbody>
                @foreach ($bill->items as $item)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="px-4 py-3">{{ $item->description }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ ucfirst($item->charge_type) }}</td>
                        <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-right">{{ money($item->amount_cents) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <dl class="px-4 py-4 border-t border-slate-200 space-y-1.5 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Previous balance</dt><dd>{{ money($bill->previous_balance_cents) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Current charges</dt><dd>{{ money($bill->charges_cents) }}</dd></div>
            @if ($bill->adjustments_cents)<div class="flex justify-between"><dt class="text-slate-500">Adjustments</dt><dd>{{ money($bill->adjustments_cents) }}</dd></div>@endif
            <div class="flex justify-between font-semibold border-t border-slate-100 pt-1.5"><dt>Total</dt><dd>{{ money($bill->total_cents) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd>{{ money($bill->amount_paid_cents) }}</dd></div>
            <div class="flex justify-between font-semibold"><dt>Outstanding</dt><dd>{{ money($bill->outstandingCents()) }}</dd></div>
        </dl>
    </div>

    @if ($bill->isPayable())
        <a href="{{ route('payments.create', ['account' => $bill->municipal_account_id]) }}" class="inline-flex rounded-xl bg-[#143E62] px-6 py-3 font-semibold text-white">Pay this bill</a>
    @endif
</x-app-layout>
