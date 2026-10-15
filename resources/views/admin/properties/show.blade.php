<x-admin-layout>
    <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold">{{ $property->fullAddress() }}</h1>
            <div class="text-sm text-slate-500">{{ $property->reference }} · Ward {{ $property->ward }} · {{ ucfirst($property->property_type) }}</div>
        </div>
        <x-status-badge :status="$property->status" />
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Status</h2>
            <form method="POST" action="{{ route('admin.properties.status', $property) }}" class="space-y-3">
                @csrf @method('PATCH')
                <select name="status" class="block w-full rounded-lg border-slate-300 text-sm">
                    @foreach (App\Models\Property::STATUSES as $s)<option value="{{ $s }}" @selected($property->status === $s)>{{ status_label($s) }}</option>@endforeach
                </select>
                <textarea name="rejection_reason" rows="2" placeholder="Reason (for rejection)" class="block w-full rounded-lg border-slate-300 text-sm">{{ $property->rejection_reason }}</textarea>
                <x-primary-button>Update status</x-primary-button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Account</h2>
            @if ($property->account)
                <dl class="text-sm space-y-1.5">
                    <div class="flex justify-between"><dt class="text-slate-500">Number</dt><dd><a class="text-[#285D9A] hover:underline" href="{{ route('admin.accounts.show', $property->account) }}">{{ $property->account->account_number }}</a></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Holder</dt><dd>{{ $property->account->holder_name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Balance</dt><dd class="font-medium">{{ money($property->account->balance_cents) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Arrears</dt><dd class="text-red-600">{{ money($property->account->arrears_cents) }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-slate-500">No account.</p>
            @endif
            <h2 class="font-semibold text-sm mt-5 mb-3">Meters</h2>
            @forelse ($property->meters as $m)
                <div class="text-sm py-1">{{ $m->meter_number }} <span class="text-slate-500">({{ $m->readings->count() }} readings)</span></div>
            @empty
                <p class="text-sm text-slate-500">None.</p>
            @endforelse
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Linked users</h2>
            @forelse ($property->propertyUsers as $pu)
                <div class="py-2 text-sm border-b border-slate-100 last:border-0 flex justify-between items-center gap-2">
                    <span>{{ $pu->user->name }} <span class="text-slate-400">({{ ucfirst($pu->relationship) }})</span></span>
                    <div class="flex items-center gap-2">
                        <x-status-badge :status="$pu->status" />
                        @if ($pu->status === 'pending')
                            <form method="POST" action="{{ route('admin.links.review', $pu) }}">@csrf
                                <button name="action" value="approve" class="text-xs text-[#285D9A] hover:underline">Approve</button>
                                <button name="action" value="reject" class="text-xs text-red-600 hover:underline">Reject</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">None.</p>
            @endforelse

            <h2 class="font-semibold text-sm mt-5 mb-3">Disconnections</h2>
            @forelse ($property->disconnectionNotices->take(5) as $n)
                <div class="py-1.5 text-sm flex justify-between">
                    <span>{{ $n->scheduled_date?->toFormattedDateString() ?? '—' }}</span>
                    <x-status-badge :status="$n->status" />
                </div>
            @empty
                <p class="text-sm text-slate-500">None.</p>
            @endforelse
        </div>
    </div>
</x-admin-layout>
