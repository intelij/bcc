<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Users</h1>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Name, email or phone" class="rounded-lg border-slate-300 text-sm">
        <select name="role" class="rounded-lg border-slate-300 text-sm">
            <option value="">All roles</option>
            @foreach ($roles as $r)<option value="{{ $r->slug }}" @selected(request('role') === $r->slug)>{{ $r->name }}</option>@endforeach
        </select>
        <select name="status" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach (['active','suspended','locked'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">Filter</button>
    </form>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[700px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr><th class="px-4 py-3">User</th><th class="px-4 py-3">Phone</th><th class="px-4 py-3">Roles</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Joined</th></tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                        <td class="px-4 py-3"><a href="{{ route('admin.users.show', $u) }}" class="font-medium text-[#285D9A] hover:underline">{{ $u->name }}</a><div class="text-xs text-slate-500">{{ $u->email }}</div></td>
                        <td class="px-4 py-3">{{ $u->phone ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $u->roles->pluck('name')->join(', ') }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$u->status" /></td>
                        <td class="px-4 py-3 text-slate-500">{{ $u->created_at->toFormattedDateString() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-admin-layout>
