<x-app-layout>
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold">Payments</h1>
        <a href="{{ route('payments.create') }}" class="rounded-lg bg-[#143E62] px-4 py-2 text-sm font-medium text-white">Make payment</a>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Account</th><th class="px-4 py-3">Provider</th><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                        <td class="px-4 py-3"><a href="{{ route('payments.show', $payment) }}" class="font-mono text-xs text-[#285D9A] hover:underline">{{ $payment->uuid }}</a></td>
                        <td class="px-4 py-3">{{ $payment->account?->account_number ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $payment->provider }}</td>
                        <td class="px-4 py-3">{{ ($payment->paid_at ?? $payment->created_at)->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">{{ money($payment->amount_cents) }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$payment->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
</x-app-layout>
