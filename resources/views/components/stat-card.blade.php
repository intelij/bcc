@props(['label', 'value', 'sub' => null, 'tone' => 'default'])
@php $tones = ['default' => 'text-slate-900', 'danger' => 'text-red-600', 'success' => 'text-emerald-700', 'warn' => 'text-amber-600']; @endphp
<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</div>
    <div class="mt-1 text-2xl font-semibold {{ $tones[$tone] ?? $tones['default'] }}">{{ $value }}</div>
    @if ($sub)<div class="mt-1 text-xs text-slate-500">{{ $sub }}</div>@endif
</div>
