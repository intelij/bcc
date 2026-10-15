<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Notifications</h1>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                <span class="font-semibold text-sm">Deliveries</span>
                <form method="GET" class="flex gap-2">
                    <select name="channel" class="rounded-lg border-slate-300 text-xs">
                        <option value="">All channels</option>
                        @foreach (['in_app','email','sms','whatsapp'] as $c)<option value="{{ $c }}" @selected(request('channel') === $c)>{{ $c }}</option>@endforeach
                    </select>
                    <select name="status" class="rounded-lg border-slate-300 text-xs">
                        <option value="">All statuses</option>
                        @foreach (['queued','sent','delivered','failed','skipped'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ status_label($s) }}</option>@endforeach
                    </select>
                    <button class="rounded-lg bg-slate-800 px-2 py-1 text-xs text-white">Filter</button>
                </form>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($deliveries as $d)
                    <div class="px-4 py-2.5 text-sm flex items-center gap-3">
                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-mono">{{ $d->channel }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="truncate">{{ $d->user->name }} — {{ $d->event }}</div>
                            <div class="text-xs text-slate-400">{{ $d->address }} · {{ $d->created_at->format('d M H:i') }}@if($d->error) · <span class="text-red-500">{{ $d->error }}</span>@endif</div>
                        </div>
                        <x-status-badge :status="$d->status" />
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-slate-500">No deliveries.</div>
                @endforelse
            </div>
            <div class="px-4 py-3 border-t border-slate-200">{{ $deliveries->links() }}</div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="font-semibold text-sm mb-3">Send notification</h2>
                <form method="POST" action="{{ route('admin.notifications.send') }}" class="space-y-3">
                    @csrf
                    <select name="user_id" class="block w-full rounded-lg border-slate-300 text-sm">
                        @foreach (App\Models\User::orderBy('name')->limit(50)->get() as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                    <select name="event" class="block w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($events as $e)<option value="{{ $e }}">{{ $e }}</option>@endforeach
                    </select>
                    <textarea name="message" rows="3" required placeholder="Message" class="block w-full rounded-lg border-slate-300 text-sm"></textarea>
                    <x-primary-button>Send</x-primary-button>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="px-4 py-3 border-b border-slate-200 font-semibold text-sm">Templates ({{ $templates->count() }})</div>
                <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                    @foreach ($templates as $t)
                        <details class="px-4 py-2.5 text-sm">
                            <summary class="cursor-pointer">{{ $t->event }} · <span class="text-slate-500">{{ $t->channel }}</span> @unless($t->is_active)<span class="text-xs text-red-500">(disabled)</span>@endunless</summary>
                            <form method="POST" action="{{ route('admin.notifications.templates.update', $t) }}" class="mt-2 space-y-2">
                                @csrf @method('PATCH')
                                <input name="subject" value="{{ $t->subject }}" placeholder="Subject" class="block w-full rounded-lg border-slate-300 text-xs">
                                <textarea name="body_template" rows="3" class="block w-full rounded-lg border-slate-300 text-xs font-mono">{{ $t->body_template }}</textarea>
                                <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked($t->is_active) class="rounded border-slate-300"> Active</label>
                                <button class="rounded-lg bg-slate-800 px-3 py-1 text-xs text-white">Save</button>
                            </form>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
