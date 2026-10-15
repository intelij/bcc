<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Audit log</h1>

    <form method="GET" class="mb-4 flex gap-3">
        <input name="action" value="{{ request('action') }}" placeholder="Action prefix (e.g. payment)" class="rounded-lg border-slate-300 text-sm">
        <input name="user" value="{{ request('user') }}" placeholder="User email" class="rounded-lg border-slate-300 text-sm">
        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">Filter</button>
    </form>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[800px]">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr><th class="px-4 py-3">When</th><th class="px-4 py-3">User</th><th class="px-4 py-3">Action</th><th class="px-4 py-3">Entity</th><th class="px-4 py-3">IP</th></tr>
            </thead>
            <tbody>
                @forelse ($logs as $l)
                    <tr class="border-b border-slate-100 last:border-0 align-top">
                        <td class="px-4 py-2.5 whitespace-nowrap text-slate-500">{{ $l->created_at->format('d M H:i:s') }}</td>
                        <td class="px-4 py-2.5">{{ $l->user?->email ?? 'system' }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs">{{ $l->action }}</td>
                        <td class="px-4 py-2.5 text-xs">
                            {{ class_basename($l->auditable_type) }}#{{ $l->auditable_id }}
                            @if ($l->old_values || $l->new_values)
                                <details class="mt-1"><summary class="text-xs text-slate-400 cursor-pointer">diff</summary>
                                    <pre class="text-[10px] bg-slate-50 rounded p-2 mt-1 overflow-x-auto">{{ json_encode(['old' => $l->old_values, 'new' => $l->new_values], JSON_PRETTY_PRINT) }}</pre>
                                </details>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-xs text-slate-400">{{ $l->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No audit entries.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-admin-layout>
