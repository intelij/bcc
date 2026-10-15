<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Refuse collection schedules</h1>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse ($schedules as $s)
                    <div class="px-4 py-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span class="font-medium">{{ $s->area }}@if($s->suburb) — {{ $s->suburb }}@endif</span>
                                <span class="text-slate-500"> · {{ $s->dayName() }}s · {{ ucfirst($s->frequency) }}</span>
                            </div>
                            <x-status-badge :status="$s->status" />
                        </div>
                        <form method="POST" action="{{ route('admin.refuse.update', $s) }}" class="mt-2 grid sm:grid-cols-4 gap-2">
                            @csrf @method('PATCH')
                            <select name="status" class="rounded-lg border-slate-300 text-xs">
                                @foreach (['active','suspended','delayed'] as $st)<option value="{{ $st }}" @selected($s->status === $st)>{{ ucfirst($st) }}</option>@endforeach
                            </select>
                            <input type="date" name="next_collection_date" value="{{ $s->next_collection_date?->toDateString() }}" class="rounded-lg border-slate-300 text-xs">
                            <input name="notes" value="{{ $s->notes }}" placeholder="Notes (holiday changes…)" class="rounded-lg border-slate-300 text-xs">
                            <button class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs text-white">Update</button>
                        </form>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-slate-500">No schedules.</div>
                @endforelse
            </div>
            <div class="px-4 py-3 border-t border-slate-200">{{ $schedules->links() }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm h-fit">
            <h2 class="font-semibold text-sm mb-3">New schedule</h2>
            <form method="POST" action="{{ route('admin.refuse.store') }}" class="space-y-3">
                @csrf
                <input name="area" placeholder="Area" required class="block w-full rounded-lg border-slate-300 text-sm">
                <div class="grid grid-cols-2 gap-2">
                    <input name="suburb" placeholder="Suburb" class="rounded-lg border-slate-300 text-sm">
                    <input name="ward" placeholder="Ward" class="rounded-lg border-slate-300 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <select name="collection_day" class="rounded-lg border-slate-300 text-sm">
                        @foreach ($days as $d => $name)<option value="{{ $d }}">{{ $name }}</option>@endforeach
                    </select>
                    <select name="frequency" class="rounded-lg border-slate-300 text-sm">
                        <option value="weekly">Weekly</option><option value="fortnightly">Fortnightly</option>
                    </select>
                </div>
                <input type="date" name="next_collection_date" class="block w-full rounded-lg border-slate-300 text-sm">
                <input name="notes" placeholder="Notes" class="block w-full rounded-lg border-slate-300 text-sm">
                <x-primary-button>Add schedule</x-primary-button>
            </form>
        </div>
    </div>
</x-admin-layout>
