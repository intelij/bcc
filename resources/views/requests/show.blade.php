<x-app-layout>
    <div class="max-w-2xl">
        <div class="flex items-start justify-between gap-3 mb-5">
            <div>
                <h1 class="text-xl font-semibold">{{ $request->ticket_number }}</h1>
                <div class="text-sm text-slate-500">{{ $request->categoryLabel() }} · {{ $request->created_at->toFormattedDateString() }}</div>
            </div>
            <x-status-badge :status="$request->status" />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm whitespace-pre-line">{{ $request->description }}</p>
            @if ($request->location_text)<p class="text-sm text-slate-500 mt-3">📍 {{ $request->location_text }}</p>@endif
            @if ($request->property)<p class="text-sm text-slate-500 mt-1">Property: {{ $request->property->fullAddress() }}</p>@endif
            @if ($request->assignee)<p class="text-sm text-slate-500 mt-1">Assigned to: {{ $request->assignee->name }}</p>@endif

            @if ($request->attachments->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($request->attachments as $att)
                        <a href="{{ $att->url() }}" class="text-xs rounded-lg border border-slate-200 px-3 py-1.5 hover:border-[#5691AD]">{{ $att->original_name }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        <h2 class="font-semibold mt-6 mb-3">Updates</h2>
        <div class="space-y-3">
            @forelse ($request->publicComments as $comment)
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="text-xs text-slate-500 mb-1">{{ $comment->user->name }} · {{ $comment->created_at->diffForHumans() }}</div>
                    <p class="text-sm">{{ $comment->body }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No updates yet.</p>
            @endforelse
        </div>

        @if (! in_array($request->status, ['closed', 'rejected']))
            <form method="POST" action="{{ route('requests.comment', $request) }}" class="mt-5">
                @csrf
                <x-input-label for="body" value="Add a comment" />
                <textarea id="body" name="body" rows="3" required class="mt-1 block w-full rounded-lg border-slate-300"></textarea>
                <x-primary-button class="mt-3">Post comment</x-primary-button>
            </form>
        @endif
    </div>
</x-app-layout>
