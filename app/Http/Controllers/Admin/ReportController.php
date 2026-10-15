<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\DisconnectionNotice;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index');
    }

    public function show(Request $request, string $report): View|\Symfony\Component\HttpFoundation\Response
    {
        $data = $request->validate([
            'from' => 'nullable|date', 'to' => 'nullable|date',
            'ward' => 'nullable|string', 'suburb' => 'nullable|string',
            'provider' => 'nullable|string', 'status' => 'nullable|string',
        ]);

        $from = isset($data['from']) ? now()->parse($data['from']) : now()->subMonths(3);
        $to = isset($data['to']) ? now()->parse($data['to'])->endOfDay() : now();

        [$title, $headers, $rows] = match ($report) {
            'collections' => $this->collections($from, $to, $data),
            'arrears' => $this->arrears($data),
            'billing' => $this->billing($from, $to, $data),
            'service_requests' => $this->serviceRequests($from, $to, $data),
            'users' => $this->users(),
            'unallocated' => $this->unallocated(),
            'disconnections' => $this->disconnections(),
            default => abort(404),
        };

        if ($request->filled('format') && $request->string('format')->toString() === 'csv') {
            return $this->csv($report, $headers, $rows);
        }

        return view('admin.reports.show', compact('title', 'headers', 'rows', 'report'));
    }

    protected function collections($from, $to, array $f): array
    {
        $rows = Payment::with('account')
            ->where('status', Payment::STATUS_SUCCESSFUL)->whereBetween('paid_at', [$from, $to])
            ->when($f['provider'] ?? null, fn ($q, $p) => $q->where('provider', $p))
            ->latest('paid_at')->get()
            ->map(fn ($p) => [
                $p->paid_at->toDateString(), $p->uuid, $p->account?->account_number,
                $p->provider, number_format($p->amount_cents / 100, 2), $p->status,
            ])->all();

        return ['Collections report', ['Date', 'Reference', 'Account', 'Provider', 'Amount (USD)', 'Status'], $rows];
    }

    protected function arrears(array $f): array
    {
        $rows = MunicipalAccount::with('property')
            ->where('arrears_cents', '>', 0)
            ->when($f['ward'] ?? null, fn ($q, $w) => $q->whereHas('property', fn ($p) => $p->where('ward', $w)))
            ->orderByDesc('arrears_cents')->get()
            ->map(fn ($a) => [
                $a->account_number, $a->holder_name, $a->property?->suburb,
                $a->property?->ward, number_format($a->arrears_cents / 100, 2),
            ])->all();

        return ['Arrears report', ['Account', 'Holder', 'Suburb', 'Ward', 'Arrears (USD)'], $rows];
    }

    protected function billing($from, $to, array $f): array
    {
        $rows = Bill::with('account')
            ->whereBetween('issue_date', [$from, $to])
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest('issue_date')->get()
            ->map(fn ($b) => [
                $b->invoice_number, $b->account->account_number, $b->issue_date->toDateString(),
                $b->due_date->toDateString(), number_format($b->total_cents / 100, 2),
                number_format($b->amount_paid_cents / 100, 2), $b->status,
            ])->all();

        return ['Billing report', ['Invoice', 'Account', 'Issued', 'Due', 'Total (USD)', 'Paid (USD)', 'Status'], $rows];
    }

    protected function serviceRequests($from, $to, array $f): array
    {
        $rows = ServiceRequest::with('user')
            ->whereBetween('created_at', [$from, $to])
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()->get()
            ->map(fn ($r) => [
                $r->ticket_number, $r->user->name, $r->categoryLabel(),
                $r->status, $r->created_at->toDateString(), $r->resolved_at?->toDateString() ?? '—',
            ])->all();

        return ['Service request report', ['Ticket', 'Reporter', 'Category', 'Status', 'Submitted', 'Resolved'], $rows];
    }

    protected function users(): array
    {
        $rows = User::with('roles')->latest()->get()
            ->map(fn ($u) => [
                $u->name, $u->email, $u->phone, $u->roles->pluck('slug')->join(', '),
                $u->status, $u->created_at->toDateString(),
            ])->all();

        return ['User report', ['Name', 'Email', 'Phone', 'Roles', 'Status', 'Registered'], $rows];
    }

    protected function unallocated(): array
    {
        $rows = Payment::where('status', Payment::STATUS_UNALLOCATED)->latest()->get()
            ->map(fn ($p) => [
                $p->created_at->toDateString(), $p->uuid, $p->reference,
                number_format($p->amount_cents / 100, 2), $p->provider,
            ])->all();

        return ['Unallocated payments', ['Date', 'UUID', 'Reference', 'Amount (USD)', 'Provider'], $rows];
    }

    protected function disconnections(): array
    {
        $rows = DisconnectionNotice::with('account.property')->latest()->get()
            ->map(fn ($d) => [
                $d->account->account_number, $d->property->fullAddress(),
                number_format($d->arrears_cents / 100, 2), $d->status,
                $d->scheduled_date?->toDateString() ?? '—',
            ])->all();

        return ['Disconnections report', ['Account', 'Property', 'Arrears (USD)', 'Status', 'Scheduled'], $rows];
    }

    protected function csv(string $name, array $headers, array $rows)
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$name-".now()->format('Ymd').'.csv',
        ]);
    }
}
