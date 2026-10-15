<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#143E62">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>{{ config('city.name') }} — {{ $title ?? 'Resident Portal' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>if ('serviceWorker' in navigator) { navigator.serviceWorker.register('/sw.js').catch(() => {}); }</script>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800">
@php $user = auth()->user(); @endphp
<div class="min-h-screen lg:flex">

    {{-- Sidebar (desktop) --}}
    <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-[#143E62] text-white z-30">
        <div class="px-5 py-6 border-b border-white/10">
            <div class="flex items-center gap-3">
                <img src="{{ asset('bcc.svg') }}" alt="{{ config('city.name') }} logo" class="h-11 w-auto rounded-lg bg-white object-contain p-0.5">
                <div>
                    <div class="font-semibold leading-tight">{{ config('city.short_name') }} City Council</div>
                    <div class="text-xs text-white/70">{{ config('city.tagline') }}</div>
                </div>
            </div>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">
            @php
                $nav = [
                    ['dashboard', 'Dashboard', 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10'],
                    ['properties.index', 'My Properties', 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-4h6v4'],
                    ['bills.index', 'My Bills', 'M9 12h6M9 16h6M9 8h1M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-2 2V3z'],
                    ['payments.index', 'Payments', 'M3 10h18M7 15h2m4 0h2M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z'],
                    ['statements.index', 'Statements', 'M9 17v-6m4 6V7m4 10v-3M5 3h14a1 1 0 011 1v16a1 1 0 01-1 1H5a1 1 0 01-1-1V4a1 1 0 011-1z'],
                    ['announcements.index', 'City Updates', 'M11 5L6 9H3v6h3l5 4V5zM15 9a3 3 0 010 6'],
                    ['water.index', 'Water', 'M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z'],
                    ['refuse.index', 'Refuse', 'M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3'],
                    ['requests.index', 'Service Requests', 'M21 12a9 9 0 11-9-9m9 9h-4m-5-4v5l3 3'],
                    ['notifications.index', 'Notifications', 'M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M10.3 21a2 2 0 003.4 0'],
                    ['help.index', 'Help', 'M9 9a3 3 0 015.8 1c0 2-2.8 2.6-2.8 4M12 17h.01M12 21a9 9 0 110-18 9 9 0 010 18z'],
                ];
                $unread = $user?->notifications()->whereNull('read_at')->count() ?? 0;
            @endphp
            @foreach ($nav as [$route, $label, $icon])
                <a href="{{ route($route) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs($route.'*') ? 'bg-white/20 font-semibold' : 'text-white/80 hover:bg-white/10' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg>
                    <span class="flex-1">{{ $label }}</span>
                    @if ($route === 'notifications.index' && $unread)
                        <span class="bg-[#FFBF00] text-[#143E62] text-xs font-bold rounded-full px-1.5 py-0.5">{{ $unread }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
        <div class="px-3 py-4 border-t border-white/10 text-xs text-white/60">
            &copy; {{ date('Y') }} {{ config('city.name') }}
        </div>
    </aside>

    <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
        {{-- Top bar --}}
        <header class="sticky top-0 z-20 bg-white border-b border-slate-200">
            <div class="px-4 sm:px-6 h-16 flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="lg:hidden flex items-center gap-2 font-semibold text-[#143E62]">
                    <img src="{{ asset('bcc.svg') }}" alt="" class="h-8 w-auto rounded bg-white object-contain p-0.5">
                    {{ config('city.short_name') }}
                </a>
                <div class="flex-1"></div>
                <a href="{{ route('notifications.index') }}" class="relative p-2 text-slate-500 hover:text-slate-800" aria-label="Notifications">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if ($unread ?? 0)
                        <span class="absolute -top-0.5 -right-0.5 bg-red-600 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1">{{ $unread }}</span>
                    @endif
                </a>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-slate-900">
                            <span class="w-8 h-8 rounded-full bg-[#143E62]/10 text-[#143E62] flex items-center justify-center font-semibold">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                            <span class="hidden sm:block">{{ $user->name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                        <x-dropdown-link :href="route('settings.edit')">Settings</x-dropdown-link>
                        @if ($user->isStaff())
                            <x-dropdown-link :href="route('admin.dashboard')">Municipal Portal</x-dropdown-link>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-6 py-6 pb-24 lg:pb-6 max-w-6xl w-full mx-auto">
            <x-flash />
            {{ $slot }}
        </main>
    </div>

    {{-- Bottom nav (mobile) --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 z-30 grid grid-cols-5 text-[10px] font-medium">
        @php
            $bottom = [
                ['dashboard', 'Home', 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10'],
                ['bills.index', 'Bills', 'M9 12h6M9 16h6M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-2 2V3z'],
                ['payments.index', 'Pay', 'M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z'],
                ['requests.index', 'Requests', 'M21 12a9 9 0 11-9-9m9 9h-4m-5-4v5l3 3'],
                ['profile.edit', 'Account', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ];
        @endphp
        @foreach ($bottom as [$route, $label, $icon])
            <a href="{{ route($route) }}" class="flex flex-col items-center gap-1 py-2 {{ request()->routeIs($route.'*') ? 'text-[#143E62]' : 'text-slate-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg>
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
</body>
</html>
