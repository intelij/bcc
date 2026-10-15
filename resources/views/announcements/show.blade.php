<x-app-layout>
    <article class="max-w-2xl">
        <div class="text-xs text-slate-500 mb-2">{{ status_label($announcement->category) }} · {{ $announcement->published_at?->toFormattedDateString() }}</div>
        <h1 class="text-2xl font-bold mb-4">{{ $announcement->title }}</h1>
        <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm leading-relaxed whitespace-pre-line">{{ $announcement->body }}</div>
    </article>
</x-app-layout>
