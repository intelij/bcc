@props(['title' => 'Nothing here yet', 'message' => null])
<div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center">
    <div class="text-slate-800 font-medium">{{ $title }}</div>
    @if ($message)<p class="mt-1 text-sm text-slate-500">{{ $message }}</p>@endif
    {{ $slot }}
</div>
