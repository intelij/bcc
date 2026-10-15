@props(['announcement' => null, 'categories' => [], 'action', 'method' => 'POST'])
<form method="POST" action="{{ $action }}" class="max-w-2xl space-y-4">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
        <div>
            <x-input-label value="Title" />
            <input name="title" value="{{ old('title', $announcement?->title) }}" required class="mt-1 block w-full rounded-lg border-slate-300">
        </div>
        <div>
            <x-input-label value="Body" />
            <textarea name="body" rows="6" required class="mt-1 block w-full rounded-lg border-slate-300">{{ old('body', $announcement?->body) }}</textarea>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <x-input-label value="Category" />
                <select name="category" class="mt-1 block w-full rounded-lg border-slate-300">
                    @foreach ($categories as $c)<option value="{{ $c }}" @selected(old('category', $announcement?->category) === $c)>{{ status_label($c) }}</option>@endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Priority" />
                <select name="priority" class="mt-1 block w-full rounded-lg border-slate-300">
                    @foreach (['low','normal','high','urgent'] as $p)<option value="{{ $p }}" @selected(old('priority', $announcement?->priority ?? 'normal') === $p)>{{ ucfirst($p) }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div><x-input-label value="Starts" /><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $announcement?->starts_at?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></div>
            <div><x-input-label value="Ends" /><input type="datetime-local" name="ends_at" value="{{ old('ends_at', $announcement?->ends_at?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></div>
        </div>
        <div>
            <x-input-label value="Target suburbs (optional, comma separated — empty = city-wide)" />
            @php $subs = $announcement?->targets->pluck('suburb')->filter()->join(', '); @endphp
            <input name="targets_suburbs" value="{{ old('targets_suburbs', $subs) }}" placeholder="e.g. Hillside, Suburbs" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
        </div>
        <x-primary-button>Save</x-primary-button>
    </div>
</form>
