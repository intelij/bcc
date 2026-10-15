<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Search{{ $q ? ': '.$q : '' }}</h1>

    @if ($results->isNotEmpty())
        @foreach ($results as $group => $items)
            @if ($items->isNotEmpty())
                <h2 class="font-semibold text-sm mt-5 mb-2">{{ $group }}</h2>
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm divide-y divide-slate-100">
                    @foreach ($items as [$label, $url])
                        <a href="{{ $url }}" class="block px-4 py-2.5 text-sm hover:bg-slate-50">{{ $label }}</a>
                    @endforeach
                </div>
            @endif
        @endforeach
    @elseif ($q)
        <x-empty-state title="No results" :message="'Nothing matched '.$q" />
    @else
        <p class="text-sm text-slate-500">Search by account number, customer name, address, phone, invoice or transaction reference.</p>
    @endif
</x-admin-layout>
