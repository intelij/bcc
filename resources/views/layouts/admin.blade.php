<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('city.name') }} — {{ $title ?? 'Municipal Portal' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-800">
@php $user = auth()->user(); @endphp
<div x-data="{ open: false }" class="min-h-screen lg:flex">
    {{-- Sidebar --}}
    <aside :class="open ? 'translate-x-0' : '-translate-x-full'" class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-200 transform transition-transform lg:translate-x-0 flex flex-col">
        <div class="px-5 py-5 border-b border-white/10 flex items-center gap-3">
            <img src="{{ asset('bcc.svg') }}" alt="{{ config('city.name') }} logo" class="h-10 w-auto rounded-lg bg-white object-contain p-0.5">
            <div>
                <div class="font-semibold text-white leading-tight">{{ config('city.short_name') }} Council</div>
                <div class="text-[11px] text-slate-400">Municipal Portal</div>
            </div>
            <button @click="open=false" class="ml-auto lg:hidden text-slate-400" aria-label="Close menu">✕</button>
        </div>
        <form action="{{ route('admin.search') }}" method="GET" class="px-3 py-3 border-b border-white/10">
            <input name="q" value="{{ request('q') }}" placeholder="Search accounts, users, bills…" class="w-full rounded-lg bg-slate-800 border-slate-700 text-sm text-slate-200 placeholder-slate-500 focus:border-[#5691AD] focus:ring-[#5691AD]">
        </form>
        <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-0.5 text-sm">
            @php
                $nav = [
                    ['admin.dashboard', 'Dashboard', null],
                    ['admin.users.index', 'Users', 'users.view'],
                    ['admin.properties.index', 'Properties', 'properties.verify'],
                    ['admin.accounts.index', 'Accounts', 'accounts.view'],
                    ['admin.billing.index', 'Billing', 'billing.manage'],
                    ['admin.payments.index', 'Payments', 'payments.view'],
                    ['admin.reconciliation.index', 'Reconciliation', 'payments.reconcile'],
                    ['admin.disconnections.index', 'Disconnections', 'disconnections.manage'],
                    ['admin.requests.index', 'Service Requests', 'requests.manage'],
                    ['admin.water.index', 'Water Services', 'content.manage'],
                    ['admin.refuse.index', 'Refuse', 'content.manage'],
                    ['admin.announcements.index', 'Announcements', 'content.manage'],
                    ['admin.notifications.index', 'Notifications', 'notifications.send'],
                    ['admin.reports.index', 'Reports', 'reports.view'],
                    ['admin.integrations.index', 'Integrations', 'integrations.manage'],
                    ['admin.audit.index', 'Audit Logs', 'audit.view'],
                    ['admin.settings.index', 'Settings', 'settings.manage'],
                ];
            @endphp
            @foreach ($nav as [$route, $label, $perm])
                @continue($perm && ! $user->hasPermission($perm))
                <a href="{{ route($route) }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs($route.'*') ? 'bg-[#285D9A] text-white font-medium' : 'text-slate-300 hover:bg-white/5' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="px-4 py-3 border-t border-white/10 text-xs text-slate-500">
            {{ $user->name }} · {{ $user->roles->pluck('slug')->map(fn($r)=>str_replace('_',' ',$r))->join(', ') }}
        </div>
    </aside>
    <div x-show="open" @click="open=false" class="fixed inset-0 bg-black/40 z-30 lg:hidden"></div>

    <div class="flex-1 flex flex-col min-h-screen min-w-0">
        <header class="sticky top-0 z-20 bg-white border-b border-slate-200 h-14 flex items-center gap-3 px-4 sm:px-6">
            <button @click="open=true" class="lg:hidden p-2 -ml-2 text-slate-600" aria-label="Open menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="text-sm text-slate-500 hidden sm:block">Municipal administration — {{ config('city.name') }}</div>
            <div class="flex-1"></div>
            <a href="{{ route('dashboard') }}" class="text-sm text-[#285D9A] hover:underline">Resident portal →</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm text-slate-600 hover:text-slate-900">Log out</button>
            </form>
        </header>
        <main class="flex-1 p-4 sm:p-6">
            <x-flash />
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
