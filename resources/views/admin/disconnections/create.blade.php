<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Schedule disconnection</h1>

    <form method="POST" action="{{ route('admin.disconnections.store') }}" class="max-w-md space-y-4 rounded-xl border border-slate-200 bg-white p-5">
        @csrf
        <div>
            <x-input-label value="Account (in arrears)" />
            <select name="municipal_account_id" class="mt-1 block w-full rounded-lg border-slate-300">
                @forelse ($accounts as $a)
                    <option value="{{ $a->id }}" @selected($selectedAccount?->id === $a->id)>{{ $a->account_number }} — {{ $a->holder_name }} ({{ money($a->arrears_cents) }} overdue)</option>
                @empty
                    <option value="">No accounts in arrears</option>
                @endforelse
            </select>
        </div>
        <div>
            <x-input-label value="Scheduled date" />
            <input type="date" name="scheduled_date" required class="mt-1 block w-full rounded-lg border-slate-300">
        </div>
        <div>
            <x-input-label value="Reason" />
            <input name="reason" required class="mt-1 block w-full rounded-lg border-slate-300" value="Non-payment of arrears">
        </div>
        <x-primary-button>Schedule & notify</x-primary-button>
    </form>
</x-admin-layout>
