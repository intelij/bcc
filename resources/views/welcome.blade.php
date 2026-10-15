<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#143E62">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>{{ config('city.name') }} — {{ config('city.tagline') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans antialiased">
<header class="bg-[#143E62] text-white">
    <div class="max-w-5xl mx-auto px-4 py-4 flex items-center gap-3">
        <img src="{{ asset('bcc.svg') }}" alt="{{ config('city.name') }} logo" class="h-12 w-auto rounded-lg bg-white object-contain p-1">
        <div>
            <div class="font-semibold">{{ config('city.name') }}</div>
            <div class="text-xs text-blue-100">{{ config('city.tagline') }}</div>
        </div>
        <nav class="ml-auto flex gap-3 text-sm">
            @auth
                <a href="{{ auth()->user()->isStaff() ? route('admin.dashboard') : route('dashboard') }}" class="rounded-lg bg-white text-[#143E62] px-4 py-2 font-medium">Portal</a>
            @else
                <a href="{{ route('login') }}" class="px-4 py-2 text-blue-50 hover:text-white">Sign in</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-white text-[#143E62] px-4 py-2 font-medium">Register</a>
            @endauth
        </nav>
    </div>
</header>
<main class="max-w-5xl mx-auto px-4 py-14">
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 max-w-2xl leading-tight">Pay rates, view water schedules and report issues — online.</h1>
    <p class="mt-4 text-lg text-slate-600 max-w-2xl">Your municipal account, bills, statements, water interruptions and refuse collection — in one secure place.</p>
    <div class="mt-8 flex gap-3">
        @auth
            <a href="{{ route('dashboard') }}" class="rounded-xl bg-[#143E62] px-6 py-3 font-semibold text-white">Go to dashboard</a>
        @else
            <a href="{{ route('register') }}" class="rounded-xl bg-[#143E62] px-6 py-3 font-semibold text-white">Create account</a>
            <a href="{{ route('login') }}" class="rounded-xl border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-700">Sign in</a>
        @endauth
    </div>
    <div class="mt-16 grid sm:grid-cols-3 gap-4">
        @foreach ([
            ['Bills & payments', 'View rates and water bills, pay electronically or by bank transfer, and download statements.'],
            ['Service alerts', 'Water-shedding schedules, interruptions and refuse collection changes — targeted to your area.'],
            ['Report & track', 'Report leaks, missed collections and road damage. Track your ticket until it\'s resolved.'],
        ] as [$t, $d])
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="font-semibold text-slate-900">{{ $t }}</div>
                <p class="mt-1.5 text-sm text-slate-600">{{ $d }}</p>
            </div>
        @endforeach
    </div>
</main>
<footer class="border-t border-slate-200 py-6 text-center text-xs text-slate-500">
    {{ config('city.name') }} · {{ config('city.support_phone') }} · Demonstration platform with fictional data
</footer>
</body>
</html>
