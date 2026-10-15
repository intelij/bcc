<x-admin-layout>
    <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold font-mono text-base">{{ $payment->uuid }}</h1>
            <div class="text-sm text-slate-500">{{ $payment->provider }} · {{ $payment->provider_reference ?? 'no provider ref' }} · source: {{ $payment->source }}</div>
        </div>
        <x-status-badge :status="$payment->status" />
    </div>

    <div class="grid lg:grid-cols-2 gap-4 max-w-4xl">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt class="text-slate-500">Amount</dt><dd class="text-lg font-semibold">{{ money($payment->amount_cents) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Account</dt><dd>{{ $payment->account?->account_number ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Property</dt><dd>{{ $payment->property?->fullAddress() ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Initiated by</dt><dd>{{ $payment->user?->name ?? 'import/manual' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Reference</dt><dd>{{ $payment->reference ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Reconciliation</dt><dd>{{ status_label($payment->reconciliation_status) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Created</dt><dd>{{ $payment->created_at->format('d M Y H:i') }}</dd></div>
                @if ($payment->paid_at)<div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd>{{ $payment->paid_at->format('d M Y H:i') }}</dd></div>@endif
                @if ($payment->failure_reason)<div class="flex justify-between"><dt class="text-slate-500">Failure</dt><dd class="text-red-600">{{ $payment->failure_reason }}</dd></div>@endif
            </dl>

            @if ($payment->allocations->isNotEmpty())
                <h3 class="font-semibold text-sm mt-4 mb-2">Allocations</h3>
                @foreach ($payment->allocations as $a)
                    <div class="flex justify-between text-sm py-1 border-b border-slate-100 last:border-0">
                        <span>{{ $a->bill?->invoice_number ?? 'Credit' }}</span><span>{{ money($a->amount_cents) }}</span>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="space-y-4">
            @if ($proof)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="font-semibold text-sm mb-2">Proof of payment</h2>
                    <a href="{{ $proof->url() }}" class="text-sm text-[#285D9A] hover:underline">{{ $proof->original_name }}</a>
                    <div class="text-xs text-slate-500">{{ $proof->mime_type }} · {{ number_format($proof->size / 1024) }} KB · uploaded {{ $proof->created_at->diffForHumans() }}</div>
                </div>
            @endif

            @can('payments.reconcile', App\Models\Payment::class)
                @if ($payment->status === 'pending' && in_array($payment->provider, ['bank_transfer', 'import', 'manual']))
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
                        <h2 class="font-semibold text-sm">Review</h2>
                        <form method="POST" action="{{ route('admin.payments.approve', $payment) }}">@csrf
                            <x-primary-button>Approve & allocate</x-primary-button>
                        </form>
                        <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" class="flex gap-2">
                            @csrf
                            <input name="reason" placeholder="Rejection reason" required class="flex-1 rounded-lg border-slate-300 text-sm">
                            <button class="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700">Reject</button>
                        </form>
                    </div>
                @endif
                @if ($payment->status === 'successful')
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <form method="POST" action="{{ route('admin.payments.refund', $payment) }}" onsubmit="return confirm('Mark as refunded?')">
                            @csrf
                            <button class="rounded-lg border border-purple-300 px-3 py-2 text-sm text-purple-700">Mark refunded</button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    </div>
</x-admin-layout>
