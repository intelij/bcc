<x-app-layout>
    <h1 class="text-xl font-semibold mb-5">Report a problem</h1>

    <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data" class="max-w-lg space-y-4 rounded-xl border border-slate-200 bg-white p-5">
        @csrf
        <div>
            <x-input-label for="category" value="Category" />
            <select id="category" name="category" class="mt-1 block w-full rounded-lg border-slate-300">
                @foreach ($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if ($properties->isNotEmpty())
            <div>
                <x-input-label for="property_id" value="Related property (optional)" />
                <select id="property_id" name="property_id" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">— General / public location —</option>
                    @foreach ($properties as $p)
                        <option value="{{ $p->id }}">{{ $p->fullAddress() }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <x-input-label for="location_text" value="Location / nearest landmark" />
            <x-text-input id="location_text" name="location_text" class="mt-1 block w-full" placeholder="e.g. Corner of Main St & 2nd Ave" :value="old('location_text')" />
        </div>
        <div>
            <x-input-label for="description" value="Describe the problem" />
            <textarea id="description" name="description" rows="4" required class="mt-1 block w-full rounded-lg border-slate-300">{{ old('description') }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="priority" value="Priority" />
            <select id="priority" name="priority" class="mt-1 block w-full rounded-lg border-slate-300">
                @foreach (['low','normal','high','urgent'] as $p)<option value="{{ $p }}" @selected(old('priority','normal') === $p)>{{ ucfirst($p) }}</option>@endforeach
            </select>
        </div>
        <div>
            <x-input-label for="photos" value="Photos (optional, up to 4MB each)" />
            <input id="photos" name="photos[]" type="file" accept=".jpg,.jpeg,.png" multiple class="mt-1 block w-full text-sm">
            <x-input-error :messages="$errors->get('photos.*')" class="mt-1" />
        </div>
        <x-primary-button>Submit request</x-primary-button>
    </form>
</x-app-layout>
