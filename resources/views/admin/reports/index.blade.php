<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Reports</h1>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach ([
            'collections' => 'Daily/monthly collections by provider',
            'arrears' => 'Accounts in arrears with balances',
            'billing' => 'Bills issued with totals and statuses',
            'service_requests' => 'Tickets by status and category',
            'users' => 'Registered users and roles',
            'unallocated' => 'Payments awaiting allocation',
            'disconnections' => 'Disconnection & restoration register',
        ] as $key => $desc)
            <a href="{{ route('admin.reports.show', $key) }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-[#5691AD]">
                <div class="font-medium">{{ status_label($key) }}</div>
                <p class="text-xs text-slate-500 mt-1">{{ $desc }}</p>
            </a>
        @endforeach
    </div>
</x-admin-layout>
