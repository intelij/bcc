<?php

namespace App\Http\Controllers;

use App\Models\MunicipalAccount;
use App\Services\BillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class StatementController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = MunicipalAccount::whereIn(
            'property_id',
            $request->user()->accessibleProperties('can_view_statements')->pluck('properties.id')
        )->with('property')->get();

        return view('statements.index', compact('accounts'));
    }

    protected function buildStatement(Request $request, MunicipalAccount $account, BillingService $billing): array
    {
        $this->authorize('viewStatements', $account);

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return $billing->statement($account, $data['from'] ?? null, $data['to'] ?? null);
    }

    public function show(Request $request, MunicipalAccount $account, BillingService $billing): View
    {
        $statement = $this->buildStatement($request, $account, $billing);

        return view('statements.show', $statement);
    }

    public function pdf(Request $request, MunicipalAccount $account, BillingService $billing)
    {
        $statement = $this->buildStatement($request, $account, $billing);

        return Pdf::loadView('statements.pdf', $statement)
            ->download('statement-'.$account->account_number.'.pdf');
    }

    public function email(Request $request, MunicipalAccount $account, BillingService $billing)
    {
        $statement = $this->buildStatement($request, $account, $billing);

        $pdf = Pdf::loadView('statements.pdf', $statement)->output();

        Mail::raw('Please find attached your municipal account statement.', function ($m) use ($request, $account, $pdf) {
            $m->to($request->user()->email)
                ->subject('Statement — '.$account->account_number)
                ->attachData($pdf, 'statement-'.$account->account_number.'.pdf', ['mime' => 'application/pdf']);
        });

        return back()->with('success', 'Statement emailed to '.$request->user()->email.'.');
    }
}
