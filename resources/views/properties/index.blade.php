<x-app-layout>
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold">My Properties</h1>
        <a href="{{ route('properties.create') }}" class="rounded-lg bg-[#143E62] px-4 py-2 text-sm font-medium text-white">Link property</a>
    </div>

    <div class="space-y-3">
        @forelse ($links as $link)
            <a href="{{ $link->status === 'approved' ? route('properties.show', $link->property) : '#' }}" class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm {{ $link->status === 'approved' ? 'hover:border-[#5691AD]' : 'opacity-75' }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium">{{ $link->property->fullAddress() }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            {{ $link->property->reference }} · Ward {{ $link->property->ward }} · {{ ucfirst($link->relationship) }}
                            @if ($link->property->account) · Acct {{ $link->property->account->account_number }} @endif
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <x-status-badge :status="$link->status" />
                        @if ($link->property->account)
                            <div class="text-xs text-slate-500 mt-1">Balance {{ money($link->property->account->balance_cents) }}</div>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <x-empty-state title="No properties linked" message="Link your municipal account number to get started." />
        @endforelse
    </div>
</x-app-layout>
