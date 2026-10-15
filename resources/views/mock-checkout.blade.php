<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DemoPay — mock checkout</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-900 font-sans antialiased text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm rounded-2xl bg-slate-800 border border-slate-700 p-6">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center font-bold">D</div>
            <div class="font-semibold">DemoPay <span class="text-[10px] font-normal bg-amber-500/20 text-amber-300 rounded px-1.5 py-0.5 ml-1">MOCK PROVIDER</span></div>
        </div>
        <p class="text-xs text-slate-400 mb-6">Simulated payment gateway. Choosing an outcome fires a signed webhook to the platform — exactly like a real provider callback.</p>

        <div class="rounded-xl bg-slate-900 p-4 mb-6">
            <div class="text-xs text-slate-400">Paying</div>
            <div class="text-2xl font-bold">{{ money($payment->amount_cents) }}</div>
            <div class="text-xs text-slate-500 mt-1">Account {{ $payment->account->account_number }} · Ref {{ $payment->provider_reference }}</div>
        </div>

        @if ($payment->status === 'pending')
            <form method="POST" action="{{ route('mock.checkout.complete', $payment->uuid) }}" class="space-y-2">
                @csrf
                <button name="outcome" value="success" class="w-full rounded-xl bg-emerald-500 py-3 font-semibold text-emerald-950">Approve payment</button>
                <button name="outcome" value="fail" class="w-full rounded-xl bg-red-500/20 border border-red-500/40 py-3 font-medium text-red-300">Decline (insufficient funds)</button>
                <button name="outcome" value="cancel" class="w-full rounded-xl border border-slate-600 py-3 font-medium text-slate-300">Cancel</button>
            </form>
        @else
            <div class="rounded-xl bg-slate-900 p-4 text-center text-sm">This payment is already {{ $payment->status }}.</div>
            <a href="{{ route('payments.show', $payment->uuid) }}" class="mt-4 block text-center text-indigo-300 text-sm">Back to portal</a>
        @endif
    </div>
</body>
</html>
