<x-app-layout>
    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-semibold">{{ $property->fullAddress() }}</h1>
            <div class="text-sm text-slate-500">{{ $property->reference }} · Ward {{ $property->ward }} · {{ ucfirst($property->property_type) }} · <x-status-badge :status="$property->status" /></div>
        </div>
    </div>

    @if ($property->account)
        @php $account = $property->account; @endphp
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
            <x-stat-card label="Account" :value="$account->account_number" :sub="$account->holder_name" />
            <x-stat-card label="Balance" :value="money($account->balance_cents)" :tone="$account->balance_cents > 0 ? 'danger' : 'success'" />
            <x-stat-card label="Arrears" :value="money($account->arrears_cents)" :tone="$account->arrears_cents > 0 ? 'danger' : 'default'" />
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="font-semibold mb-3">Bills</h2>
                @forelse ($property->account->bills as $bill)
                    <a href="{{ route('bills.show', $bill) }}" class="flex justify-between py-2 text-sm border-b border-slate-100 last:border-0">
                        <span>{{ $bill->invoice_number }}</span>
                        <span class="flex items-center gap-2">{{ money($bill->total_cents) }} <x-status-badge :status="$bill->status" /></span>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">No bills yet.</p>
                @endforelse
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="font-semibold mb-3">Water meters</h2>
                @forelse ($property->meters as $meter)
                    <div class="py-2 text-sm border-b border-slate-100 last:border-0">
                        <div class="font-medium">{{ $meter->meter_number }}</div>
                        <div class="text-slate-500 text-xs">
                            @if ($meter->latestReading)
                                Last reading {{ number_format($meter->latestReading->current_reading) }} kL on {{ $meter->latestReading->reading_date->toFormattedDateString() }} ({{ $meter->latestReading->reading_type }})
                            @else
                                No readings recorded.
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No meters on record.</p>
                @endforelse
            </div>
        </div>
    @endif

    @can('manageTenants', $property)
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold mb-3">Tenants & authorized users</h2>
            @foreach ($property->propertyUsers->where('relationship', '!=', 'owner') as $pu)
                <div class="flex items-center justify-between py-2 text-sm border-b border-slate-100 last:border-0">
                    <div>
                        <span class="font-medium">{{ $pu->user->name }}</span>
                        <span class="text-slate-500">({{ $pu->user->email }}) — {{ ucfirst($pu->relationship) }}</span>
                        <div class="text-xs text-slate-400">
                            Bills: {{ $pu->can_view_bills ? '✓' : '✗' }} · Account: {{ $pu->can_view_account ? '✓' : '✗' }} · Pay: {{ $pu->can_pay ? '✓' : '✗' }} · Requests: {{ $pu->can_submit_requests ? '✓' : '✗' }} · Statements: {{ $pu->can_view_statements ? '✓' : '✗' }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-status-badge :status="$pu->status" />
                        <form method="POST" action="{{ route('links.destroy', $pu) }}" onsubmit="return confirm('Revoke access?')">@csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:underline">Revoke</button>
                        </form>
                    </div>
                </div>
            @endforeach
            <form method="POST" action="{{ route('properties.tenants', $property) }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <x-input-label for="email" value="Tenant email (must be registered)" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full max-w-sm" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div class="flex flex-wrap gap-4 text-sm">
                    @foreach (['can_view_bills' => 'View bills', 'can_view_account' => 'View account', 'can_pay' => 'Make payments', 'can_submit_requests' => 'Submit requests', 'can_view_statements' => 'View statements'] as $field => $label)
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="{{ $field }}" value="1" checked class="rounded border-slate-300 text-[#285D9A]"> {{ $label }}</label>
                    @endforeach
                </div>
                <x-primary-button>Authorize tenant</x-primary-button>
            </form>
        </div>
    @endcan
</x-app-layout>
