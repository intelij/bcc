<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLog;
use App\Models\PaymentWebhook;
use App\Models\SyncJob;
use App\Services\MunicipalSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(): View
    {
        $outbox = collect(glob(storage_path('app/mock-outbox/*.json')) ?: [])
            ->map(fn ($f) => ['file' => basename($f), 'payload' => json_decode(file_get_contents($f), true)])
            ->sortByDesc('file')->take(50)->values();

        return view('admin.integrations.index', [
            'syncJobs' => SyncJob::with('trigger')->latest()->paginate(15),
            'logs' => IntegrationLog::latest('created_at')->paginate(25),
            'webhooks' => PaymentWebhook::latest()->paginate(15),
            'outbox' => $outbox,
            'drivers' => [
                'payments' => config('integrations.payments.default'),
                'sms' => config('integrations.notifications.sms.driver'),
                'whatsapp' => config('integrations.notifications.whatsapp.driver'),
                'municipal_billing' => config('integrations.municipal_billing.driver'),
            ],
        ]);
    }

    public function sync(Request $request, MunicipalSyncService $sync): RedirectResponse
    {
        $data = $request->validate(['entity' => 'required|in:accounts,bills,payments,properties']);

        $job = $sync->sync($data['entity'], $request->user());

        return back()->with('success', "Sync {$job->status}: {$job->records_processed} records processed.");
    }
}
