<x-app-layout>
    <h1 class="text-xl font-semibold mb-5">Statements</h1>
    <div class="space-y-3">
        @forelse ($accounts as $account)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-48">
                    <div class="font-medium">{{ $account->account_number }}</div>
                    <div class="text-sm text-slate-500">{{ $account->property->fullAddress() }}</div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('statements.show', $account) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">View</a>
                    <a href="{{ route('statements.pdf', $account) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">PDF</a>
                    <form method="POST" action="{{ route('statements.email', $account) }}">@csrf
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">Email</button>
                    </form>
                </div>
            </div>
        @empty
            <x-empty-state title="No statementable accounts" message="Only linked properties where you have statement permission appear here." />
        @endforelse
    </div>
</x-app-layout>
