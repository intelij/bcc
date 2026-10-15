<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Water services</h1>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="px-4 py-3 border-b border-slate-200 font-semibold text-sm">Interruptions</div>
                <div class="divide-y divide-slate-100">
                    @forelse ($interruptions as $i)
                        <div class="px-4 py-3 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium">{{ $i->title }}</div>
                                    <div class="text-slate-500">{{ $i->area }}@if($i->suburb), {{ $i->suburb }}@endif · {{ $i->starts_at->format('d M H:i') }} → {{ $i->expected_restoration_at?->format('d M H:i') ?? '?' }}</div>
                                </div>
                                <x-status-badge :status="$i->status" />
                            </div>
                            <form method="POST" action="{{ route('admin.water.interruptions.update', $i) }}" class="mt-2 flex gap-2">
                                @csrf @method('PATCH')
                                <select name="status" class="rounded-lg border-slate-300 text-xs">
                                    @foreach (App\Models\ServiceInterruption::STATUSES as $s)<option value="{{ $s }}" @selected($i->status === $s)>{{ status_label($s) }}</option>@endforeach
                                </select>
                                <button class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs text-white">Update</button>
                            </form>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-500">No interruptions.</div>
                    @endforelse
                </div>
                <div class="px-4 py-3 border-t border-slate-200">{{ $interruptions->links() }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="px-4 py-3 border-b border-slate-200 font-semibold text-sm">Shedding schedules</div>
                <div class="divide-y divide-slate-100">
                    @forelse ($schedules as $s)
                        <div class="px-4 py-3 text-sm flex items-center justify-between">
                            <span>{{ $s->area }}@if($s->suburb) — {{ $s->suburb }}@endif · {{ $s->dayName() }} {{ substr($s->start_time,0,5) }}–{{ substr($s->end_time,0,5) }}</span>
                            <form method="POST" action="{{ route('admin.water.schedules.destroy', $s) }}" onsubmit="return confirm('Remove schedule?')">@csrf @method('DELETE')
                                <button class="text-xs text-red-600 hover:underline">Remove</button>
                            </form>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-slate-500">No schedules.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="font-semibold text-sm mb-3">New interruption</h2>
                <form method="POST" action="{{ route('admin.water.interruptions.store') }}" class="space-y-3">
                    @csrf
                    <input name="title" placeholder="Title" required class="block w-full rounded-lg border-slate-300 text-sm">
                    <div class="grid grid-cols-2 gap-2">
                        <select name="type" class="rounded-lg border-slate-300 text-sm">
                            <option value="planned">Planned</option><option value="emergency">Emergency</option><option value="maintenance">Maintenance</option>
                        </select>
                        <select name="status" class="rounded-lg border-slate-300 text-sm">
                            <option value="planned">Planned</option><option value="active">Active</option>
                        </select>
                    </div>
                    <input name="area" placeholder="Area" required class="block w-full rounded-lg border-slate-300 text-sm">
                    <div class="grid grid-cols-2 gap-2">
                        <input name="suburb" placeholder="Suburb" class="rounded-lg border-slate-300 text-sm">
                        <input name="ward" placeholder="Ward" class="rounded-lg border-slate-300 text-sm">
                    </div>
                    <div><x-input-label value="Starts" class="text-xs" /><input type="datetime-local" name="starts_at" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><x-input-label value="Expected restoration" class="text-xs" /><input type="datetime-local" name="expected_restoration_at" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></div>
                    <input name="reason" placeholder="Reason" class="block w-full rounded-lg border-slate-300 text-sm">
                    <x-primary-button>Publish & notify area</x-primary-button>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="font-semibold text-sm mb-3">New shedding schedule</h2>
                <form method="POST" action="{{ route('admin.water.schedules.store') }}" class="space-y-3">
                    @csrf
                    <input name="area" placeholder="Area" required class="block w-full rounded-lg border-slate-300 text-sm">
                    <div class="grid grid-cols-2 gap-2">
                        <input name="suburb" placeholder="Suburb" class="rounded-lg border-slate-300 text-sm">
                        <input name="ward" placeholder="Ward" class="rounded-lg border-slate-300 text-sm">
                    </div>
                    <select name="day_of_week" class="block w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($days as $d => $name)<option value="{{ $d }}">{{ $name }}</option>@endforeach
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="time" name="start_time" required class="rounded-lg border-slate-300 text-sm">
                        <input type="time" name="end_time" required class="rounded-lg border-slate-300 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="effective_from" required class="rounded-lg border-slate-300 text-sm">
                        <input type="date" name="effective_to" class="rounded-lg border-slate-300 text-sm">
                    </div>
                    <x-primary-button>Add schedule</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
