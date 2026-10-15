<x-app-layout>
    <h1 class="text-xl font-semibold mb-5">Refuse collection</h1>

    <h2 class="font-semibold mb-3">Your area</h2>
    <div class="space-y-3 mb-8">
        @forelse ($schedules as $s)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex items-center justify-between">
                <div>
                    <div class="font-medium">{{ $s->area }}@if($s->suburb) — {{ $s->suburb }}@endif</div>
                    <div class="text-sm text-slate-600">{{ $s->dayName() }}s · {{ ucfirst($s->frequency) }}
                        @if($s->next_collection_date)· Next: {{ $s->next_collection_date->toFormattedDateString() }}@endif</div>
                    @if($s->notes)<div class="text-xs text-amber-700 mt-1">{{ $s->notes }}</div>@endif
                </div>
                <x-status-badge :status="$s->status" />
            </div>
        @empty
            <x-empty-state title="No schedule for your area" />
        @endforelse
    </div>

    <h2 class="font-semibold mb-3">All areas</h2>
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[480px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr><th class="px-4 py-3">Area</th><th class="px-4 py-3">Day</th><th class="px-4 py-3">Frequency</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody>
                @foreach ($all as $s)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="px-4 py-3">{{ $s->area }}@if($s->suburb) — {{ $s->suburb }}@endif</td>
                        <td class="px-4 py-3">{{ $s->dayName() }}</td>
                        <td class="px-4 py-3">{{ ucfirst($s->frequency) }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$s->status" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
