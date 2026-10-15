<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\BillingService;
use App\Services\PaymentService;
use App\Services\ReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::with('account.property', 'user')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('uuid', 'like', '%'.$request->q.'%')
                ->orWhere('reference', 'like', '%'.$request->q.'%')
                ->orWhere('provider_reference', 'like', '%'.$request->q.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('provider'), fn ($q) => $q->where('provider', $request->string('provider')))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'statuses' => Payment::STATUSES,
        ]);
    }

    public function show(Payment $payment): View
    {
        $payment->load('account.property', 'user', 'allocations.bill');
        $proof = Attachment::where('attachable_type', Payment::class)
            ->where('attachable_id', $payment->id)->latest()->first();

        return view('admin.payments.show', compact('payment', 'proof'));
    }

    /** Manual payment capture (counter cash, phone receipt). */
    public function storeManual(Request $request, ReconciliationService $recon): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('payments.reconcile'), 403);

        $data = $request->validate([
            'municipal_account_id' => 'required|exists:municipal_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
        ]);

        $payment = $recon->recordManual(
            MunicipalAccount::findOrFail($data['municipal_account_id']),
            (int) round($data['amount'] * 100),
            $request->user(),
            $data['reference'] ?? null,
        );

        return redirect()->route('admin.payments.show', $payment)->with('success', 'Manual payment recorded and allocated.');
    }

    /** Approve a bank-transfer proof of payment. */
    public function approve(Request $request, Payment $payment, PaymentService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('payments.reconcile'), 403);
        abort_unless($payment->status === Payment::STATUS_PENDING, 422, 'Only pending payments can be approved.');

        $service->approveManual($payment, $request->user());

        return back()->with('success', 'Payment approved and allocated.');
    }

    public function reject(Request $request, Payment $payment, PaymentService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('payments.reconcile'), 403);
        $data = $request->validate(['reason' => 'required|string|max:500']);

        $service->rejectManual($payment, $request->user(), $data['reason']);

        return back()->with('success', 'Payment rejected.');
    }

    public function refund(Request $request, Payment $payment, PaymentService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('payments.reconcile'), 403);
        abort_unless($payment->status === Payment::STATUS_SUCCESSFUL, 422);

        $old = $payment->only('status');
        $payment->forceFill(['status' => Payment::STATUS_REFUNDED])->save();
        app(AuditService::class)->record('payment.refunded', $payment, $old, ['status' => 'refunded']);
        app(BillingService::class)->recomputeAccount($payment->account);

        return back()->with('success', 'Payment marked refunded.');
    }
}
