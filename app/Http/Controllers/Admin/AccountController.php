<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MunicipalAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = MunicipalAccount::with('property')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('account_number', 'like', '%'.$request->q.'%')
                ->orWhere('holder_name', 'like', '%'.$request->q.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('account_number')->paginate(20)->withQueryString();

        return view('admin.accounts.index', compact('accounts'));
    }

    public function show(MunicipalAccount $account): View
    {
        $account->load('property', 'bills.items', 'payments', 'disconnectionNotices');

        return view('admin.accounts.show', compact('account'));
    }
}
