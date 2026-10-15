@props(['status'])
@php
    $colors = [
        'paid' => 'bg-emerald-100 text-emerald-800', 'successful' => 'bg-emerald-100 text-emerald-800',
        'active' => 'bg-emerald-100 text-emerald-800', 'verified' => 'bg-emerald-100 text-emerald-800',
        'approved' => 'bg-emerald-100 text-emerald-800', 'resolved' => 'bg-emerald-100 text-emerald-800',
        'restored' => 'bg-emerald-100 text-emerald-800', 'published' => 'bg-emerald-100 text-emerald-800',
        'delivered' => 'bg-emerald-100 text-emerald-800', 'completed' => 'bg-emerald-100 text-emerald-800',
        'reconciled' => 'bg-emerald-100 text-emerald-800', 'matched' => 'bg-emerald-100 text-emerald-800',
        'issued' => 'bg-blue-100 text-blue-800', 'sent' => 'bg-blue-100 text-blue-800',
        'assigned' => 'bg-blue-100 text-blue-800', 'acknowledged' => 'bg-blue-100 text-blue-800',
        'pending' => 'bg-amber-100 text-amber-800', 'processing' => 'bg-amber-100 text-amber-800',
        'partially_paid' => 'bg-amber-100 text-amber-800', 'scheduled' => 'bg-amber-100 text-amber-800',
        'submitted' => 'bg-amber-100 text-amber-800', 'in_progress' => 'bg-amber-100 text-amber-800',
        'restoring' => 'bg-amber-100 text-amber-800', 'notice_issued' => 'bg-amber-100 text-amber-800',
        'planned' => 'bg-amber-100 text-amber-800', 'draft' => 'bg-slate-100 text-slate-700',
        'overdue' => 'bg-red-100 text-red-800', 'failed' => 'bg-red-100 text-red-800',
        'rejected' => 'bg-red-100 text-red-800', 'suspended' => 'bg-red-100 text-red-800',
        'disconnected' => 'bg-red-100 text-red-800', 'unallocated' => 'bg-red-100 text-red-800',
        'disputed' => 'bg-orange-100 text-orange-800', 'cancelled' => 'bg-slate-200 text-slate-600',
        'refunded' => 'bg-purple-100 text-purple-800', 'expired' => 'bg-slate-200 text-slate-600',
        'closed' => 'bg-slate-200 text-slate-600', 'locked' => 'bg-red-100 text-red-800',
        'warning' => 'bg-amber-100 text-amber-800', 'awaiting_information' => 'bg-amber-100 text-amber-800',
        'restoration_pending' => 'bg-amber-100 text-amber-800', 'skipped' => 'bg-slate-100 text-slate-600',
        'delayed' => 'bg-amber-100 text-amber-800', 'invalid' => 'bg-red-100 text-red-800',
    ];
@endphp
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $colors[$status] ?? 'bg-slate-100 text-slate-700' }}">{{ status_label($status) }}</span>
