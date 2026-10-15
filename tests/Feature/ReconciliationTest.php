<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_matches_by_account_number(): void
    {
        $staff = $this->makeUser('finance');
        $account = $this->makePropertyWithAccount();

        $csv = "date,reference,amount\n2026-09-30,{$account->account_number},45.00\n";
        $file = UploadedFile::fake()->createWithContent('bank.csv', $csv);

        $import = app(ReconciliationService::class)->importCsv($file, $staff);

        $this->assertEquals(1, $import->rows_total);
        $this->assertEquals(1, $import->rows_matched);
        $payment = Payment::where('provider', 'import')->first();
        $this->assertEquals($account->id, $payment->municipal_account_id);
        $this->assertEquals(4500, $payment->amount_cents);
        $this->assertEquals('successful', $payment->status);
    }

    public function test_unmatched_csv_row_becomes_unallocated(): void
    {
        $staff = $this->makeUser('finance');
        $csv = "date,reference,amount\n2026-09-30,GARBAGE-REF,12.34\n";
        $file = UploadedFile::fake()->createWithContent('bank.csv', $csv);

        $import = app(ReconciliationService::class)->importCsv($file, $staff);

        $this->assertEquals(1, $import->rows_unmatched);
        $this->assertEquals('unallocated', Payment::where('provider', 'import')->first()->status);
    }

    public function test_reference_containing_account_number_matches(): void
    {
        $staff = $this->makeUser('finance');
        $account = $this->makePropertyWithAccount();

        $csv = "date,reference,amount\n2026-09-30,PMT {$account->account_number} SEP,10.00\n";
        $file = UploadedFile::fake()->createWithContent('bank.csv', $csv);

        app(ReconciliationService::class)->importCsv($file, $staff);

        $this->assertEquals($account->id, Payment::where('provider', 'import')->first()->municipal_account_id);
    }

    public function test_manual_allocation_assigns_account_and_reconciles(): void
    {
        $staff = $this->makeUser('finance');
        $account = $this->makePropertyWithAccount();
        $payment = Payment::create([
            'municipal_account_id' => null, 'amount_cents' => 5000, 'provider' => 'import',
            'status' => 'unallocated', 'reconciliation_status' => 'unallocated', 'paid_at' => now(),
        ]);

        app(ReconciliationService::class)->allocate($payment, $account, $staff);

        $payment->refresh();
        $this->assertEquals($account->id, $payment->municipal_account_id);
        $this->assertEquals('successful', $payment->status);
        $this->assertEquals('reconciled', $payment->reconciliation_status);
    }
}
