<?php

namespace App\Services;

use App\Contracts\MunicipalBillingProviderInterface;
use App\Models\IntegrationLog;
use App\Models\MunicipalAccount;
use App\Models\SyncJob;
use App\Models\User;

/** Scheduled/manual synchronization with the upstream billing system. */
class MunicipalSyncService
{
    protected MunicipalBillingProviderInterface $provider;

    public function __construct(protected BillingService $billing)
    {
        $this->provider = app(config('integrations.municipal_billing.'.config('integrations.municipal_billing.driver').'.class'));
    }

    public function sync(string $entity, ?User $actor = null): SyncJob
    {
        $job = SyncJob::create([
            'provider' => $this->provider->key(),
            'entity' => $entity,
            'status' => 'running',
            'started_at' => now(),
            'triggered_by' => $actor?->id,
        ]);

        $start = microtime(true);
        try {
            $count = match ($entity) {
                'bills' => $this->syncBills(),
                'accounts' => count($this->provider->fetchAccounts()),
                default => throw new \InvalidArgumentException("Unsupported sync entity [$entity]"),
            };

            $job->forceFill(['status' => 'completed', 'records_processed' => $count, 'finished_at' => now()])->save();
            IntegrationLog::create([
                'integration' => 'municipal_billing', 'direction' => 'inbound',
                'operation' => "sync.$entity", 'status' => 'success',
                'response_summary' => "$count records", 'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);
        } catch (\Throwable $e) {
            $job->forceFill(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()])->save();
            IntegrationLog::create([
                'integration' => 'municipal_billing', 'direction' => 'inbound',
                'operation' => "sync.$entity", 'status' => 'failed',
                'response_summary' => substr($e->getMessage(), 0, 500),
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);
        }

        return $job;
    }

    /** Pull upstream bills into local storage (idempotent by invoice number). */
    protected function syncBills(): int
    {
        $count = 0;
        foreach (MunicipalAccount::all() as $account) {
            foreach ($this->provider->fetchBills($account->account_number) as $raw) {
                $exists = $account->bills()->where('invoice_number', $raw['invoice_number'])->exists();
                if ($exists) {
                    continue;
                }
                $this->billing->issueBill($account, [
                    'invoice_number' => $raw['invoice_number'],
                    'period_start' => $raw['period_start'],
                    'period_end' => $raw['period_end'],
                    'issue_date' => $raw['issue_date'],
                    'due_date' => $raw['due_date'],
                ], $raw['items']);
                $count++;
            }
            $account->forceFill(['last_synced_at' => now()])->save();
        }

        return $count;
    }
}
