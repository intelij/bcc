<x-admin-layout>
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-xl font-semibold">{{ $title }}</h1>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="rounded-lg bg-[#285D9A] px-4 py-2 text-sm font-medium text-white">Export CSV</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border-slate-300 text-sm">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border-slate-300 text-sm">
        <input name="ward" value="{{ request('ward') }}" placeholder="Ward" class="rounded-lg border-slate-300 text-sm w-24">
        <input name="suburb" value="{{ request('suburb') }}" placeholder="Suburb" class="rounded-lg border-slate-300 text-sm w-32">
        <input name="provider" value="{{ request('provider') }}" placeholder="Provider" class="rounded-lg border-slate-300 text-sm w-28">
        <input name="status" value="{{ request('status') }}" placeholder="Status" class="rounded-lg border-slate-300 text-sm w-28">
        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">Filter</button>
    </form>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr>@foreach ($headers as $h)<th class="px-4 py-3">{{ $h }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b border-slate-100 last:border-0">
                        @foreach ($row as $cell)<td class="px-4 py-2.5">{{ $cell }}</td>@endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($headers) }}" class="px-4 py-8 text-center text-slate-500">No data for these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
