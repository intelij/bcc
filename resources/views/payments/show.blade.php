<x-app-layout>
    <h1 class="text-xl font-semibold mb-5">Payment {{ $payment->status === 'successful' ? 'receipt' : 'status' }}</h1>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5 max-w-xl">
        <div class="flex items-center justify-between mb-4">
            <x-status-badge :status="$payment->status" />
            <span class="text-2xl font-semibold">{{ money($payment->amount_cents) }}</span>
        </div>
        <dl class="text-sm space-y-2">
            <div class="flex justify-between"><dt class="text-slate-500">Reference</dt><dd class="font-mono text-xs">{{ $payment->uuid }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Account</dt><dd>{{ $payment->account?->account_number ?? 'Unallocated' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Property</dt><dd>{{ $payment->property?->fullAddress() ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Provider</dt><dd>{{ $payment->provider }} ({{ $payment->provider_reference ?? '—' }})</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Initiated</dt><dd>{{ $payment->created_at->format('d M Y H:i') }}</dd></div>
            @if ($payment->paid_at)<div class="flex justify-between"><dt class="text-slate-500">Confirmed</dt><dd>{{ $payment->paid_at->format('d M Y H:i') }}</dd></div>@endif
            @if ($payment->failure_reason)<div class="flex justify-between"><dt class="text-slate-500">Reason</dt><dd class="text-red-600">{{ $payment->failure_reason }}</dd></div>@endif
        </dl>

        @if ($payment->allocations->isNotEmpty())
            <h3 class="mt-5 mb-2 text-sm font-semibold">Allocated to</h3>
            <ul class="text-sm text-slate-600 space-y-1">
                @foreach ($payment->allocations as $a)
                    <li>{{ $a->bill?->invoice_number ?? 'Account credit' }} — {{ money($a->amount_cents) }}</li>
                @endforeach
            </ul>
        @endif

        @if ($payment->provider === 'bank_transfer' && $payment->status === 'pending')
            @php $init = $payment->meta['initiation']['instructions'] ?? config('city.bank'); @endphp
            <div class="mt-5 rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm">
                <h3 class="font-semibold mb-2">Bank transfer instructions</h3>
                <dl class="space-y-1">
                    <div class="flex justify-between"><dt class="text-slate-500">Bank</dt><dd>{{ $init['bank'] ?? '' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Account name</dt><dd>{{ $init['account_name'] ?? '' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Account number</dt><dd>{{ $init['account_number'] ?? '' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Branch</dt><dd>{{ $init['branch'] ?? '' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Reference</dt><dd class="font-mono">{{ $init['reference'] ?? $payment->account?->account_number }}</dd></div>
                </dl>
            </div>
            <form method="POST" action="{{ route('payments.proof', $payment) }}" enctype="multipart/form-data" class="mt-4">
                @csrf
                <x-input-label for="proof" value="Upload proof of payment (JPG, PNG or PDF, max 4MB)" />
                <input id="proof" name="proof" type="file" accept=".jpg,.jpeg,.png,.pdf" required class="mt-1 block w-full text-sm">
                <x-input-error :messages="$errors->get('proof')" class="mt-1" />
                <x-primary-button class="mt-3">Submit proof</x-primary-button>
            </form>
        @endif

        @if ($payment->status === 'pending' && $payment->provider === 'mock')
            <a href="{{ route('mock.checkout.show', $payment->uuid) }}" class="mt-4 inline-block rounded-lg bg-[#143E62] px-4 py-2 text-sm font-medium text-white">Return to checkout</a>
        @endif
    </div>
</x-app-layout>
