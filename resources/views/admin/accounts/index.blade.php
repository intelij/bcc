<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Municipal accounts</h1>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Account number or holder" class="rounded-lg border-slate-300 text-sm">
        <select name="status" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach (['active','suspended','closed'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">Filter</button>
    </form>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[700px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr><th class="px-4 py-3">Account</th><th class="px-4 py-3">Holder</th><th class="px-4 py-3">Property</th><th class="px-4 py-3 text-right">Balance</th><th class="px-4 py-3 text-right">Arrears</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody>
                @forelse ($accounts as $a)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                        <td class="px-4 py-3"><a href="{{ route('admin.accounts.show', $a) }}" class="font-medium text-[#285D9A] hover:underline">{{ $a->account_number }}</a></td>
                        <td class="px-4 py-3">{{ $a->holder_name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $a->property->address_line }}, {{ $a->property->suburb }}</td>
                        <td class="px-4 py-3 text-right">{{ money($a->balance_cents) }}</td>
                        <td class="px-4 py-3 text-right text-red-600">{{ money($a->arrears_cents) }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$a->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $accounts->links() }}</div>
</x-admin-layout>
