<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Settings</h1>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">System settings</h2>
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-3">
                @csrf
                @forelse ($settings as $s)
                    <div>
                        <x-input-label value="{{ $s->key }}" />
                        <input name="settings[{{ $s->key }}]" value="{{ $s->value }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No settings defined.</p>
                @endforelse
                @if ($settings->isNotEmpty())<x-primary-button>Save</x-primary-button>@endif
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">FAQs</h2>
            @foreach ($faqs as $f)
                <div class="py-2 border-b border-slate-100 last:border-0 text-sm flex justify-between items-start gap-3">
                    <div><div class="font-medium">{{ $f->question }}</div><div class="text-xs text-slate-500">{{ $f->category }}</div></div>
                    <form method="POST" action="{{ route('admin.settings.faqs.destroy', $f) }}">@csrf @method('DELETE')
                        <button class="text-xs text-red-600">Delete</button>
                    </form>
                </div>
            @endforeach
            <form method="POST" action="{{ route('admin.settings.faqs.store') }}" class="mt-4 space-y-2">
                @csrf
                <input name="question" placeholder="Question" required class="block w-full rounded-lg border-slate-300 text-sm">
                <textarea name="answer" placeholder="Answer" required rows="2" class="block w-full rounded-lg border-slate-300 text-sm"></textarea>
                <input name="category" placeholder="Category" value="general" required class="block w-full rounded-lg border-slate-300 text-sm">
                <x-primary-button>Add FAQ</x-primary-button>
            </form>
        </div>
    </div>
</x-admin-layout>
