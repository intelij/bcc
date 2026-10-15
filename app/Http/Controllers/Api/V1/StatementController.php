<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\MunicipalAccount;
use App\Services\BillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatementController extends ApiController
{
    /** Statement lines between optional from/to dates. */
    public function show(Request $request, MunicipalAccount $account, BillingService $billing): JsonResponse
    {
        $this->authorize('viewStatements', $account);

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return response()->json(['data' => $billing->statement($account, $data['from'] ?? null, $data['to'] ?? null)]);
    }

    /** PDF download — same document as the web portal. */
    public function pdf(Request $request, MunicipalAccount $account, BillingService $billing)
    {
        $this->authorize('viewStatements', $account);

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $statement = $billing->statement($account, $data['from'] ?? null, $data['to'] ?? null);

        return Pdf::loadView('statements.pdf', $statement)
            ->download('statement-'.$account->account_number.'.pdf');
    }
}
