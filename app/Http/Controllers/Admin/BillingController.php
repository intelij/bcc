<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\MeterReading;
use App\Models\MunicipalAccount;
use App\Models\WaterMeter;
use App\Services\AuditService;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $bills = Bill::with('account.property')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('invoice_number', 'like', '%'.$request->q.'%')
                ->orWhereHas('account', fn ($a) => $a->where('account_number', 'like', '%'.$request->q.'%'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('issue_date')->paginate(20)->withQueryString();

        return view('admin.billing.index', [
            'bills' => $bills,
            'statuses' => Bill::STATUSES,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.billing.create', [
            'accounts' => MunicipalAccount::with('property')->where('status', 'active')->orderBy('account_number')->get(),
            'chargeTypes' => [BillItem::TYPE_RATES, BillItem::TYPE_WATER, BillItem::TYPE_REFUSE, BillItem::TYPE_SEWER, BillItem::TYPE_OTHER],
            'selectedAccount' => MunicipalAccount::find($request->integer('account')),
        ]);
    }

    public function store(Request $request, BillingService $billing): RedirectResponse
    {
        $data = $request->validate([
            'municipal_account_id' => 'required|exists:municipal_accounts,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'due_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.charge_type' => 'required|string',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $account = MunicipalAccount::findOrFail($data['municipal_account_id']);

        $items = collect($data['items'])->map(fn ($i) => [
            'charge_type' => $i['charge_type'],
            'description' => $i['description'],
            'quantity' => (int) $i['quantity'],
            'unit_price_cents' => (int) round($i['unit_price'] * 100),
            'amount_cents' => (int) round($i['unit_price'] * 100) * (int) $i['quantity'],
        ])->all();

        $bill = $billing->issueBill($account, $data, $items, $request->user());

        return redirect()->route('admin.billing.show', $bill)->with('success', 'Bill '.$bill->invoice_number.' issued.');
    }

    public function show(Bill $bill): View
    {
        $bill->load('items.meterReading', 'account.property', 'allocations.payment');

        return view('admin.billing.show', compact('bill'));
    }

    public function transition(Request $request, Bill $bill, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:disputed,cancelled,issued']);
        $old = $bill->only('status');
        $bill->forceFill(['status' => $data['status']])->save();
        $audit->record('bill.status_changed', $bill, $old, $bill->only('status'));

        return back()->with('success', 'Bill status updated.');
    }

    /** Record a meter reading against a property's meter. */
    public function storeReading(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'water_meter_id' => 'required|exists:water_meters,id',
            'current_reading' => 'required|integer|min:0',
            'reading_date' => 'required|date',
            'reading_type' => 'required|in:actual,estimated,corrected',
        ]);

        $meter = WaterMeter::findOrFail($data['water_meter_id']);
        $previous = (int) ($meter->latestReading?->current_reading ?? 0);

        MeterReading::create([
            'water_meter_id' => $meter->id,
            'previous_reading' => $previous,
            'current_reading' => $data['current_reading'],
            'consumption' => max(0, $data['current_reading'] - $previous),
            'reading_date' => $data['reading_date'],
            'reading_type' => $data['reading_type'],
            'source' => 'manual',
            'recorded_by' => $request->user()->id,
        ]);

        $audit->record('meter.reading_recorded', $meter, null, ['current' => $data['current_reading']]);

        return back()->with('success', 'Meter reading recorded.');
    }
}
