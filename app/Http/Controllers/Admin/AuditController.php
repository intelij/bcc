<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', $request->string('action').'%'))
            ->when($request->filled('user'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('email', 'like', '%'.$request->user.'%')))
            ->latest('created_at')->paginate(30)->withQueryString();

        return view('admin.audit.index', compact('logs'));
    }
}
