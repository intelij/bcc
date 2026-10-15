<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Attachment;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\PaymentManager;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Payments API. The backend is authoritative for payment state — clients
 * initiate, then poll `GET /payments/{uuid}` or wait for the webhook-driven
 * notification. A client return/redirect NEVER finalizes a payment.
 */
class PaymentController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $propertyIds = $user->accessibleProperties('can_view_account')->pluck('properties.id');

        $payments = Payment::where(fn ($q) => $q->where('user_id', $user->id)
            ->orWhereIn('property_id', $propertyIds))
            ->with('account')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(20)->withQueryString();

        $payments->setCollection($payments->getCollection()->map(fn ($p) => $this->paymentData($p)));

        return response()->json($payments);
    }

    /** Available payment providers — the client renders these generically. */
    public function providers(PaymentManager $manager): JsonResponse
    {
        return response()->json([
            'data' => collect($manager->all())->map(fn ($p, $key) => [
                'key' => $key,
                'label' => $p->label(),
            ])->values(),
        ]);
    }

    /**
     * Initiate a payment. Idempotent: pass a stable `idempotency_key`
     * (client-generated UUID) — retries return the same payment, never a
     * duplicate. Response `initiation` tells the client what to do next
     * (redirect URL, bank instructions, etc.) without provider logic.
     */
    public function store(Request $request, PaymentService $service): JsonResponse
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

        return response()->json([
            'data' => $this->paymentData($result['payment']->loadMissing('account')),
            'initiation' => $result['initiation'],
            'reused' => $result['reused'] ?? false,
        ], ($result['reused'] ?? false) ? 200 : 201);
    }

    /** Poll for status — status changes only via verified webhooks/staff. */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);
        $payment->load('account', 'allocations.bill');

        return response()->json(['data' => $this->paymentData($payment)]);
    }

    /** Upload proof for a pending bank-transfer payment. */
    public function uploadProof(Request $request, Payment $payment): JsonResponse
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

        $attachment = Attachment::create([
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

        return response()->json([
            'data' => ['attachment_id' => $attachment->id],
            'message' => 'Proof of payment uploaded. Finance will review and allocate it.',
        ], 201);
    }
}
