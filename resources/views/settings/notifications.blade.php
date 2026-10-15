<x-app-layout>
    <h1 class="text-xl font-semibold mb-5">Notification preferences</h1>
    <p class="text-sm text-slate-500 mb-4">Choose which channels receive each type of notification. In-app messages always appear in your notification centre.</p>

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
            <table class="w-full text-sm min-w-[560px]">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Event</th>
                        @foreach ($channels as $channel)
                            <th class="px-4 py-3 text-center">{{ ucfirst(str_replace('_', ' ', $channel)) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="px-4 py-3">{{ ucwords(strtolower(str_replace('_', ' ', $event))) }}</td>
                            @foreach ($channels as $channel)
                                @php $on = $service->enabled($user ?? auth()->user(), $event, $channel); @endphp
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" name="prefs[{{ $event }}.{{ $channel }}]" value="1" @checked($on)
                                           class="rounded border-slate-300 text-[#285D9A]" @disabled($channel === 'in_app' && false)>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-primary-button class="mt-4">Save preferences</x-primary-button>
    </form>
</x-app-layout>
