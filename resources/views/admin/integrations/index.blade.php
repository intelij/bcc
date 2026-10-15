<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Integrations</h1>

    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        All external providers are currently <strong>mock adapters</strong>. Replace credentials + driver in <code>.env</code> when real API specs are supplied. See INTEGRATIONS.md.
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        @foreach ($drivers as $name => $driver)
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs uppercase text-slate-500">{{ str_replace('_', ' ', $name) }}</div>
                <div class="font-semibold mt-1">{{ $driver }} <span class="text-xs font-normal text-amber-600">(mock)</span></div>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                <span class="font-semibold text-sm">Municipal billing sync</span>
                <form method="POST" action="{{ route('admin.integrations.sync') }}">@csrf
                    <button name="entity" value="bills" class="rounded-lg bg-[#285D9A] px-3 py-1.5 text-xs font-medium text-white">Run bill sync now</button>
                </form>
            </div>
            <div class="divide-y divide-slate-100 text-sm">
                @forelse ($syncJobs as $j)
                    <div class="px-4 py-2.5 flex items-center justify-between">
                        <div>{{ $j->entity }} <span class="text-xs text-slate-400">{{ $j->provider }} · {{ $j->created_at->diffForHumans() }} · {{ $j->records_processed }} records</span></div>
                        <x-status-badge :status="$j->status" />
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-slate-500">No sync jobs yet.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="px-4 py-3 border-b border-slate-200 font-semibold text-sm">Payment webhooks</div>
            <div class="divide-y divide-slate-100 text-sm">
                @forelse ($webhooks as $w)
                    <div class="px-4 py-2.5">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs">{{ $w->provider }} / {{ $w->event_type ?? '?' }}</span>
                            <x-status-badge :status="$w->status" />
                        </div>
                        <div class="text-xs text-slate-400">{{ $w->created_at->format('d M H:i:s') }}@if($w->error) · <span class="text-red-500">{{ $w->error }}</span>@endif</div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-slate-500">No webhooks received.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="px-4 py-3 border-b border-slate-200 font-semibold text-sm">Mock outbox (SMS / WhatsApp)</div>
            <div class="divide-y divide-slate-100 text-sm max-h-72 overflow-y-auto">
                @forelse ($outbox as $m)
                    <div class="px-4 py-2.5">
                        <div class="text-xs text-slate-500">{{ $m['payload']['channel'] }} → {{ $m['payload']['to'] ?? 'n/a' }} · {{ $m['payload']['at'] ?? '' }}</div>
                        <div class="text-sm">{{ Str::limit($m['payload']['body'] ?? '', 140) }}</div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-slate-500">Outbox empty.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="px-4 py-3 border-b border-slate-200 font-semibold text-sm">Integration log</div>
            <div class="divide-y divide-slate-100 text-sm max-h-72 overflow-y-auto">
                @forelse ($logs as $l)
                    <div class="px-4 py-2.5">
                        <div class="flex justify-between"><span>{{ $l->integration }} · {{ $l->operation }}</span><x-status-badge :status="$l->status" /></div>
                        <div class="text-xs text-slate-400">{{ $l->direction }} · {{ $l->created_at->format('d M H:i:s') }}@if ($l->duration_ms) · {{ $l->duration_ms }}ms @endif</div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-slate-500">No log entries.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
