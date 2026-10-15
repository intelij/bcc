<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankPaymentImport;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Services\ReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReconciliationController extends Controller
{
    public function index(): View
    {
        return view('admin.reconciliation.index', [
            'unallocated' => Payment::whereIn('status', [Payment::STATUS_UNALLOCATED])
                ->orWhere(fn ($q) => $q->where('status', Payment::STATUS_SUCCESSFUL)->where('reconciliation_status', 'partially_allocated'))
                ->with('account.property')->latest()->paginate(20),
            'imports' => BankPaymentImport::with('importer')->latest()->paginate(10),
            'accounts' => MunicipalAccount::orderBy('account_number')->get(),
        ]);
    }

    public function import(Request $request, ReconciliationService $recon): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $import = $recon->importCsv($request->file('file'), $request->user());

        return back()->with('success', "Imported {$import->rows_total} rows: {$import->rows_matched} matched, {$import->rows_unmatched} unallocated.");
    }

    public function allocate(Request $request, Payment $payment, ReconciliationService $recon): RedirectResponse
    {
        $data = $request->validate(['municipal_account_id' => 'required|exists:municipal_accounts,id']);

        $recon->allocate($payment, MunicipalAccount::findOrFail($data['municipal_account_id']), $request->user());

        return back()->with('success', 'Payment allocated to account.');
    }
}
