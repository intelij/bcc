<x-app-layout>
    <h1 class="text-xl font-semibold mb-5">Make a payment</h1>

    @if ($accounts->isEmpty())
        <x-empty-state title="No payable accounts" message="Link and verify a property first." />
    @else
        <form method="POST" action="{{ route('payments.store') }}" class="max-w-md space-y-4 rounded-xl border border-slate-200 bg-white p-5"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
            <div>
                <x-input-label for="municipal_account_id" value="Account" />
                <select id="municipal_account_id" name="municipal_account_id" class="mt-1 block w-full rounded-lg border-slate-300">
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected($selectedAccount?->id === $account->id)>
                            {{ $account->account_number }} — {{ $account->property->suburb }} (balance {{ money($account->balance_cents) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="amount" value="Amount ({{ config('city.currency') }})" />
                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.50" class="mt-1 block w-full" :value="old('amount', number_format(max(0, ($selectedAccount?->balance_cents ?? 0) / 100), 2, '.', ''))" required />
                <x-input-error :messages="$errors->get('amount')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Payment method" />
                <div class="mt-2 space-y-2">
                    @foreach ($providers as $key => $provider)
                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer hover:border-[#5691AD]">
                            <input type="radio" name="provider" value="{{ $key }}" @checked($loop->first) class="text-[#285D9A]">
                            <span>
                                <span class="block text-sm font-medium">{{ $provider->label() }}</span>
                                @if ($key === 'mock')
                                    <span class="block text-xs text-slate-500">Demo gateway — simulated provider callback</span>
                                @elseif ($key === 'bank_transfer')
                                    <span class="block text-xs text-slate-500">EFT to the council account; upload proof for reconciliation</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
            <x-primary-button class="w-full justify-center">Continue</x-primary-button>
            <p class="text-xs text-slate-500">Your payment is only confirmed after the provider confirms it — never assume a redirect means success.</p>
        </form>
    @endif
</x-app-layout>
