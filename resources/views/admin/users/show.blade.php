<x-admin-layout>
    <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold">{{ $user->name }}</h1>
            <div class="text-sm text-slate-500">{{ $user->email }} · {{ $user->phone ?? 'no phone' }} · ID {{ $user->national_id ?? '—' }}</div>
        </div>
        <x-status-badge :status="$user->status" />
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Account controls</h2>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-3">
                @csrf @method('PATCH')
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                        @foreach (['active','suspended','locked'] as $s)<option value="{{ $s }}" @selected($user->status === $s)>{{ ucfirst($s) }}</option>@endforeach
                    </select>
                </div>
                @can('users.manage', App\Models\User::class)
                    <div>
                        <x-input-label value="Roles" />
                        <div class="mt-1 space-y-1.5">
                            @foreach ($roles as $role)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($user->roles->contains($role)) class="rounded border-slate-300 text-[#285D9A]">
                                    {{ $role->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endcan
                <x-primary-button>Save</x-primary-button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Linked properties</h2>
            @forelse ($user->propertyUsers as $pu)
                <div class="py-2 text-sm border-b border-slate-100 last:border-0 flex justify-between">
                    <span>{{ $pu->property->fullAddress() }} <span class="text-slate-400">({{ ucfirst($pu->relationship) }})</span></span>
                    <x-status-badge :status="$pu->status" />
                </div>
            @empty
                <p class="text-sm text-slate-500">None.</p>
            @endforelse

            <h2 class="font-semibold text-sm mt-5 mb-3">Service requests</h2>
            @forelse ($user->serviceRequests->take(5) as $r)
                <div class="py-2 text-sm border-b border-slate-100 last:border-0 flex justify-between">
                    <a href="{{ route('admin.requests.show', $r) }}" class="text-[#285D9A] hover:underline">{{ $r->ticket_number }}</a>
                    <x-status-badge :status="$r->status" />
                </div>
            @empty
                <p class="text-sm text-slate-500">None.</p>
            @endforelse
        </div>
    </div>
</x-admin-layout>
