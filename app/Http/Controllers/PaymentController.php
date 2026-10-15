<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\PaymentManager;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $propertyIds = $user->accessibleProperties('can_view_account')->pluck('properties.id');

        $payments = Payment::where(fn ($q) => $q->where('user_id', $user->id)
            ->orWhereIn('property_id', $propertyIds))
            ->with('account')
            ->latest()->paginate(15);

        return view('payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $accounts = MunicipalAccount::whereIn(
            'property_id',
            $request->user()->accessibleProperties('can_pay')->pluck('properties.id')
        )->with('property')->where('status', 'active')->get();

        return view('payments.create', [
            'accounts' => $accounts,
            'providers' => app(PaymentManager::class)->all(),
            'selectedAccount' => $accounts->firstWhere('id', $request->integer('account')),
        ]);
    }

    public function store(Request $request, PaymentService $service): RedirectResponse
    {
        $data = $request->validate([
            'municipal_account_id' => ['required', 'integer', 'exists:municipal_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.50', 'max:100000'],
            'provider' => ['required', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        $account = MunicipalAccount::findOrFail($data['municipal_account_id']);
        $this->authorize('pay', $account);

        $result = $service->initiate(
            $request->user(),
            $account,
            (int) round($data['amount'] * 100),
            $data['provider'],
            $data['idempotency_key'] ?? (string) Str::uuid(),
        );

        $payment = $result['payment'];
        $initiation = $result['initiation'];

        if (($initiation['type'] ?? null) === 'redirect' && ! ($result['reused'] ?? false)) {
            return redirect()->away($initiation['redirect_url']);
        }

        // Bank transfer → show instructions + proof upload.
        return redirect()->route('payments.show', $payment->uuid)
            ->with('success', 'Payment initiated. Follow the instructions shown.');
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);
        $payment->load('account.property', 'allocations.bill');

        return view('payments.show', compact('payment'));
    }

    /** Upload proof of payment for a bank transfer. */
    public function uploadProof(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('view', $payment);
        abort_unless($payment->provider === 'bank_transfer' && $payment->status === 'pending', 422);

        $data = $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $file = $data['proof'];
        $path = $file->store('payment-proofs/'.$payment->id, 'private');

        $payment->meta = array_merge($payment->meta ?? [], ['proof_uploaded' => true]);
        $payment->save();

        Attachment::create([
            'attachable_type' => Payment::class,
            'attachable_id' => $payment->id,
            'uploaded_by' => $request->user()->id,
            'disk' => 'private',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        app(AuditService::class)->record('payment.proof_uploaded', $payment);

        return back()->with('success', 'Proof of payment uploaded. Finance will review and allocate it.');
    }
}
