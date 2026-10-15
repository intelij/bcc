<x-admin-layout>
    <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold">{{ $request->ticket_number }}</h1>
            <div class="text-sm text-slate-500">{{ $request->categoryLabel() }} · by {{ $request->user->name }} · {{ $request->created_at->toFormattedDateString() }}</div>
        </div>
        <x-status-badge :status="$request->status" />
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm whitespace-pre-line">{{ $request->description }}</p>
                @if ($request->location_text)<p class="text-sm text-slate-500 mt-2">Location: {{ $request->location_text }}</p>@endif
                @if ($request->property)<p class="text-sm text-slate-500">Property: {{ $request->property->fullAddress() }}</p>@endif
                @if ($request->attachments->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($request->attachments as $att)
                            <a href="{{ $att->url() }}" class="text-xs rounded-lg border border-slate-200 px-3 py-1.5">{{ $att->original_name }}</a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="font-semibold text-sm mb-3">Comments</h2>
                @foreach ($request->comments as $c)
                    <div class="py-2 border-b border-slate-100 last:border-0 text-sm {{ $c->is_internal ? 'bg-amber-50 -mx-4 px-4' : '' }}">
                        <div class="text-xs text-slate-500">{{ $c->user->name }} · {{ $c->created_at->diffForHumans() }} @if($c->is_internal)· <span class="text-amber-700 font-medium">internal</span>@endif</div>
                        {{ $c->body }}
                    </div>
                @endforeach
                <form method="POST" action="{{ route('admin.requests.comment', $request) }}" class="mt-3 space-y-2">
                    @csrf
                    <textarea name="body" rows="2" required class="block w-full rounded-lg border-slate-300 text-sm" placeholder="Reply or internal note…"></textarea>
                    <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="is_internal" value="1" class="rounded border-slate-300"> Internal note (hidden from resident)</label>
                    <x-primary-button>Post</x-primary-button>
                </form>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm h-fit">
            <h2 class="font-semibold text-sm mb-3">Manage</h2>
            <form method="POST" action="{{ route('admin.requests.update', $request) }}" class="space-y-3">
                @csrf @method('PATCH')
                <div>
                    <x-input-label value="Status" />
                    <select name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($statuses as $s)<option value="{{ $s }}" @selected($request->status === $s)>{{ status_label($s) }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Assign to" />
                    <select name="assigned_to" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                        <option value="">—</option>
                        @foreach ($officers as $o)<option value="{{ $o->id }}" @selected($request->assigned_to === $o->id)>{{ $o->name }}</option>@endforeach
                    </select>
                </div>
                <x-primary-button>Update</x-primary-button>
            </form>
        </div>
    </div>
</x-admin-layout>
