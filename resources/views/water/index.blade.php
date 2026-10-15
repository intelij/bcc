<x-app-layout>
    <h1 class="text-xl font-semibold mb-5">Water services</h1>

    <h2 class="font-semibold mb-3">Current & planned interruptions</h2>
    <div class="space-y-3 mb-8">
        @forelse ($interruptions as $i)
            <div class="rounded-xl border {{ $i->status === 'active' ? 'border-red-300 bg-red-50/50' : 'border-amber-300 bg-amber-50/50' }} p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium">{{ $i->title }}</div>
                        <div class="text-sm text-slate-600 mt-0.5">{{ $i->area }}@if($i->suburb), {{ $i->suburb }}@endif @if($i->ward)· Ward {{ $i->ward }}@endif</div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ ucfirst($i->type) }} · From {{ $i->starts_at->format('d M Y H:i') }}
                            @if($i->expected_restoration_at)· Expected restoration {{ $i->expected_restoration_at->format('d M Y H:i') }}@endif
                            @if($i->reason)· {{ $i->reason }}@endif
                        </div>
                    </div>
                    <x-status-badge :status="$i->status" />
                </div>
            </div>
        @empty
            <x-empty-state title="No current interruptions" message="You're all clear — no planned or active interruptions for your area." />
        @endforelse
    </div>

    <h2 class="font-semibold mb-3">Water-shedding schedule</h2>
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto mb-8">
        <table class="w-full text-sm min-w-[480px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr><th class="px-4 py-3">Area</th><th class="px-4 py-3">Day</th><th class="px-4 py-3">Off</th><th class="px-4 py-3">Effective</th></tr>
            </thead>
            <tbody>
                @forelse ($schedules as $s)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="px-4 py-3">{{ $s->area }}@if($s->suburb) — {{ $s->suburb }}@endif</td>
                        <td class="px-4 py-3">{{ $s->dayName() }}</td>
                        <td class="px-4 py-3">{{ substr($s->start_time,0,5) }}–{{ substr($s->end_time,0,5) }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $s->effective_from->toFormattedDateString() }}{{ $s->effective_to ? ' – '.$s->effective_to->toFormattedDateString() : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No shedding schedule for your area.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="font-semibold mb-3">Consumption history</h2>
    <div class="space-y-3 mb-8">
        @forelse ($meters as $meter)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="font-medium text-sm">Meter {{ $meter->meter_number }} — {{ $meter->property->address_line }}</div>
                <table class="w-full text-xs mt-2">
                    <thead class="text-slate-500 text-left"><tr><th>Date</th><th class="text-right">Reading</th><th class="text-right">kL used</th><th>Type</th></tr></thead>
                    <tbody>
                        @foreach ($meter->readings->take(8) as $r)
                            <tr class="border-t border-slate-100">
                                <td class="py-1.5">{{ $r->reading_date->toFormattedDateString() }}</td>
                                <td class="py-1.5 text-right font-mono">{{ number_format($r->current_reading) }}</td>
                                <td class="py-1.5 text-right">{{ number_format($r->consumption) }}</td>
                                <td class="py-1.5 pl-3 text-slate-500">{{ ucfirst($r->reading_type) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <x-empty-state title="No meters" message="Meters appear here once your property is linked." />
        @endforelse
    </div>

    @if ($past->isNotEmpty())
        <h2 class="font-semibold mb-3">Resolved interruptions</h2>
        <div class="space-y-2">
            @foreach ($past as $i)
                <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm flex justify-between">
                    <span>{{ $i->title }} — {{ $i->area }}</span>
                    <span class="text-slate-500">resolved {{ $i->resolved_at?->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
