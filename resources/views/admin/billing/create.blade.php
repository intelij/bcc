<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Issue a bill</h1>

    <form method="POST" action="{{ route('admin.billing.store') }}" x-data="{ rows: [{charge_type:'rates',description:'',quantity:1,unit_price:''}] }" class="max-w-3xl space-y-4">
        @csrf
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
            <div>
                <x-input-label value="Account" />
                <select name="municipal_account_id" class="mt-1 block w-full rounded-lg border-slate-300">
                    @foreach ($accounts as $a)
                        <option value="{{ $a->id }}" @selected($selectedAccount?->id === $a->id)>{{ $a->account_number }} — {{ $a->holder_name }} ({{ $a->property->suburb }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid sm:grid-cols-3 gap-3">
                <div><x-input-label value="Period start" /><input type="date" name="period_start" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></div>
                <div><x-input-label value="Period end" /><input type="date" name="period_end" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></div>
                <div><x-input-label value="Due date" /><input type="date" name="due_date" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold text-sm mb-3">Line items</h2>
            <template x-for="(row, i) in rows" :key="i">
                <div class="grid grid-cols-12 gap-2 mb-2">
                    <select :name="`items[${i}][charge_type]`" x-model="row.charge_type" class="col-span-3 rounded-lg border-slate-300 text-sm">
                        @foreach ($chargeTypes as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                    </select>
                    <input :name="`items[${i}][description]`" x-model="row.description" placeholder="Description" required class="col-span-5 rounded-lg border-slate-300 text-sm">
                    <input :name="`items[${i}][quantity]`" x-model="row.quantity" type="number" min="1" placeholder="Qty" required class="col-span-1 rounded-lg border-slate-300 text-sm">
                    <input :name="`items[${i}][unit_price]`" x-model="row.unit_price" type="number" step="0.01" min="0" placeholder="USD" required class="col-span-2 rounded-lg border-slate-300 text-sm">
                    <button type="button" @click="rows.splice(i,1)" x-show="rows.length > 1" class="col-span-1 text-red-600 text-lg">×</button>
                </div>
            </template>
            <button type="button" @click="rows.push({charge_type:'rates',description:'',quantity:1,unit_price:''})" class="text-sm text-[#285D9A] hover:underline">+ Add line</button>
        </div>

        <x-primary-button>Issue bill</x-primary-button>
    </form>

    <div class="mt-8 max-w-3xl rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="font-semibold text-sm mb-3">Record meter reading</h2>
        <form method="POST" action="{{ route('admin.billing.readings') }}" class="grid sm:grid-cols-5 gap-3 items-end">
            @csrf
            <div class="sm:col-span-2">
                <x-input-label value="Meter" />
                <select name="water_meter_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    @foreach (App\Models\WaterMeter::with('property')->get() as $m)
                        <option value="{{ $m->id }}">{{ $m->meter_number }} — {{ $m->property->suburb }}</option>
                    @endforeach
                </select>
            </div>
            <div><x-input-label value="Reading (kL)" /><input type="number" name="current_reading" min="0" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></div>
            <div><x-input-label value="Date" /><input type="date" name="reading_date" value="{{ today()->toDateString() }}" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></div>
            <div>
                <select name="reading_type" class="block w-full rounded-lg border-slate-300 text-sm">
                    <option value="actual">Actual</option><option value="estimated">Estimated</option><option value="corrected">Corrected</option>
                </select>
            </div>
            <x-primary-button class="sm:col-span-5 w-fit">Record</x-primary-button>
        </form>
    </div>
</x-admin-layout>
