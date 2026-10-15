<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\DisconnectionNotice;
use App\Models\Faq;
use App\Models\MeterReading;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyUser;
use App\Models\RefuseSchedule;
use App\Models\Role;
use App\Models\ServiceInterruption;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestComment;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WaterMeter;
use App\Models\WaterSchedule;
use App\Services\BillingService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Fictional demonstration data — all names, addresses, IDs and
 * transactions are invented for the demo. No real customer data.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'Password123';

    public function run(): void
    {
        // --- Demo users (documented in README) ---
        $mk = function (string $name, string $email, string $role, string $phone) {
            $u = User::factory()->create([
                'name' => $name, 'email' => $email, 'phone' => $phone,
                'email_verified_at' => now(),
                'password' => Hash::make(self::PASSWORD),
            ]);
            $u->roles()->attach(Role::where('slug', $role)->first());

            return $u;
        };

        $resident = $mk('Takudzwa Moyo', 'resident@example.com', 'resident', '+263771000001');
        $owner = $mk('Rutendo Chikafu', 'owner@example.com', 'property_owner', '+263771000002');
        $tenant = $mk('Blessing Ncube', 'tenant@example.com', 'tenant', '+263771000003');
        $support = $mk('Farai Dube', 'support@example.com', 'customer_service', '+263771000004');
        $finance = $mk('Nyasha Sithole', 'finance@example.com', 'finance', '+263771000005');
        $content = $mk('Chipo Mangeri', 'content@example.com', 'content_admin', '+263771000006');
        $admin = $mk('Admin User', 'admin@example.com', 'admin', '+263771000007');

        $extraResidents = collect(range(1, 13))->map(fn ($i) => $mk(
            fake()->name(), "resident{$i}@example.com", 'resident', '+2637711'.str_pad((string) $i, 5, '0', STR_PAD_LEFT)
        ));

        // --- Properties (fictional Bulawayo addresses) ---
        $areas = [
            ['12 Joshua Mqabuko Nkomo St', 'Bulawayo CBD', '1'], ['45 Fife Street', 'Suburbs', '2'],
            ['7 Matopos Road', 'Hillside', '8'], ['23 9th Avenue', 'North End', '3'],
            ['89 Pumula Road', 'Pumula South', '19'], ['3 Masotsha Ndlovu Ave', 'Emakhandeni', '14'],
            ['56 Plumtree Road', 'Belmont', '5'], ['101 Luveve Road', 'Luveve', '15'],
            ['9 Khami Road', 'Mzilikazi', '12'], ['17 Cowdray Park Drive', 'Cowdray Park', '28'],
        ];

        $properties = collect($areas)->map(function ($a, $i) {
            return Property::create([
                'reference' => 'PROP-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'address_line' => $a[0], 'suburb' => $a[1], 'ward' => $a[2], 'city' => 'Bulawayo',
                'property_type' => $i === 0 ? 'commercial' : 'residential',
                'status' => Property::STATUS_VERIFIED, 'verified_at' => now(),
            ]);
        });

        // Accounts + meters
        $accounts = $properties->map(function ($p, $i) {
            WaterMeter::create(['meter_number' => 'WM-'.str_pad((string) (1000 + $i), 6, '0', STR_PAD_LEFT), 'property_id' => $p->id]);

            return MunicipalAccount::create([
                'account_number' => 'BYO-'.(100001 + $i),
                'property_id' => $p->id,
                'holder_name' => fake()->name(),
                'status' => 'active',
            ]);
        });

        // Links: owner owns 3 properties, resident 1, tenant on resident's? tenant on owner's property
        $link = fn (User $u, Property $p, string $rel, array $perms = []) => PropertyUser::create([
            'property_id' => $p->id, 'user_id' => $u->id, 'relationship' => $rel,
            'status' => 'approved', 'authorized_by' => $admin->id, 'authorized_at' => now(),
        ] + $perms);

        $link($owner, $properties[0], 'owner');
        $link($owner, $properties[1], 'owner');
        $link($owner, $properties[2], 'owner');
        $link($resident, $properties[3], 'owner');
        $link($tenant, $properties[1], 'tenant', ['can_view_statements' => false]);
        $extraResidents->each(fn ($u, $i) => isset($properties[4 + $i]) ? $link($u, $properties[4 + $i], 'owner') : null);

        // Meter readings (3 months each)
        WaterMeter::all()->each(function ($meter) {
            $reading = rand(900, 1200);
            foreach ([3, 2, 1] as $monthsAgo) {
                $prev = $reading;
                $reading += rand(8, 30);
                MeterReading::create([
                    'water_meter_id' => $meter->id,
                    'previous_reading' => $prev, 'current_reading' => $reading,
                    'consumption' => $reading - $prev,
                    'reading_date' => now()->subMonths($monthsAgo)->startOfMonth()->addDays(3),
                    'reading_type' => rand(0, 4) === 0 ? 'estimated' : 'actual',
                    'source' => 'manual',
                ]);
            }
        });

        // Bills: 3 per account (30 total) with mixed statuses
        $billing = app(BillingService::class);
        $accounts->each(function ($account, $i) use ($billing) {
            foreach ([2, 1, 0] as $m) {
                $period = now()->subMonths($m);
                $water = rand(12, 40) * 100;
                $bill = $billing->issueBill($account, [
                    'invoice_number' => sprintf('INV-%s-%s-%02d', $account->account_number, $period->format('Ym'), $m + 1),
                    'period_start' => $period->copy()->startOfMonth(),
                    'period_end' => $period->copy()->endOfMonth(),
                    'issue_date' => $period->copy()->startOfMonth(),
                    'due_date' => $period->copy()->endOfMonth(),
                ], [
                    ['charge_type' => 'rates', 'description' => 'Property rates — '.$period->format('M Y'), 'quantity' => 1, 'unit_price_cents' => 4500],
                    ['charge_type' => 'water', 'description' => 'Water consumption', 'quantity' => 1, 'unit_price_cents' => $water],
                    ['charge_type' => 'refuse', 'description' => 'Refuse collection', 'quantity' => 1, 'unit_price_cents' => 800],
                    ['charge_type' => 'sewer', 'description' => 'Sewerage charge', 'quantity' => 1, 'unit_price_cents' => 1200],
                ]);
            }
        });

        // Payments: 30 mixed across accounts/providers/statuses
        $payments = app(PaymentService::class);
        $ownerUser = $owner;
        $accounts->each(function ($account, $i) use ($payments, $billing) {
            $bills = $account->bills()->whereIn('status', ['issued', 'overdue'])->oldest('issue_date')->get();
            $payer = $account->property->propertyUsers()->where('status', 'approved')->first()?->user;
            foreach ($bills as $j => $bill) {
                if ($j === 0 || ($i + $j) % 3 === 0 || ($j === 1 && $i % 2 === 0)) {
                    // fully pay oldest bills for most accounts
                    $p = Payment::create([
                        'municipal_account_id' => $account->id, 'property_id' => $account->property_id,
                        'user_id' => $payer?->id, 'amount_cents' => $bill->total_cents,
                        'provider' => ['mock', 'bank_transfer', 'import'][$i % 3],
                        'payment_method' => 'mobile_money', 'reference' => $account->account_number,
                        'status' => 'successful', 'provider_reference' => 'DEMO-'.$i.'-'.$j,
                        'reconciliation_status' => 'reconciled', 'paid_at' => $bill->issue_date->copy()->addDays(5),
                        'source' => 'portal',
                    ]);
                    $payments->allocateToBills($p);
                    $bill->syncStatus();
                } elseif ($j === 1 && $i % 4 === 0) {
                    // partial payment
                    $p = Payment::create([
                        'municipal_account_id' => $account->id, 'property_id' => $account->property_id,
                        'user_id' => $payer?->id, 'amount_cents' => (int) ($bill->total_cents * 0.4),
                        'provider' => 'mock', 'payment_method' => 'mobile_money',
                        'reference' => $account->account_number, 'status' => 'successful',
                        'provider_reference' => 'DEMO-P'.$i, 'reconciliation_status' => 'reconciled',
                        'paid_at' => $bill->issue_date->copy()->addDays(10), 'source' => 'portal',
                    ]);
                    $payments->allocateToBills($p);
                    $bill->syncStatus();
                }
            }
            $billing->recomputeAccount($account);
        });

        // Top up: extra smaller payments so the ledger shows ~30 transactions.
        $accounts->each(function ($account, $i) {
            if ($i >= 8) {
                return;
            }
            $payer = $account->property->propertyUsers()->where('status', 'approved')->first()?->user;
            Payment::create([
                'municipal_account_id' => $account->id, 'property_id' => $account->property_id,
                'user_id' => $payer?->id, 'amount_cents' => rand(10, 60) * 100,
                'provider' => 'mock', 'payment_method' => 'mobile_money',
                'reference' => $account->account_number, 'status' => 'successful',
                'provider_reference' => 'DEMO-X'.$i, 'reconciliation_status' => 'reconciled',
                'paid_at' => now()->subDays(rand(1, 15)), 'source' => 'portal',
            ]);
        });
        $accounts->each(fn ($a) => $billing->recomputeAccount($a));

        // A few failed/pending/unallocated payments for realism
        Payment::create(['municipal_account_id' => $accounts[0]->id, 'property_id' => $properties[0]->id, 'user_id' => $owner->id, 'amount_cents' => 5000, 'provider' => 'mock', 'payment_method' => 'mobile_money', 'reference' => $accounts[0]->account_number, 'status' => 'failed', 'failure_reason' => 'Insufficient funds', 'source' => 'portal']);
        Payment::create(['municipal_account_id' => null, 'user_id' => null, 'amount_cents' => 7500, 'provider' => 'import', 'payment_method' => 'bank_transfer', 'reference' => 'UNKNOWN REF 8834', 'status' => 'unallocated', 'reconciliation_status' => 'unallocated', 'source' => 'import', 'paid_at' => now()->subDays(2)]);
        Payment::create(['municipal_account_id' => $accounts[3]->id, 'property_id' => $properties[3]->id, 'user_id' => $resident->id, 'amount_cents' => 3000, 'provider' => 'bank_transfer', 'payment_method' => 'bank_transfer', 'reference' => $accounts[3]->account_number, 'status' => 'pending', 'source' => 'portal', 'meta' => ['initiation' => ['type' => 'instructions', 'instructions' => config('city.bank')]]]);

        // A disconnection notice in progress
        $arrearsAccount = $accounts->sortByDesc('arrears_cents')->first();
        if ($arrearsAccount && $arrearsAccount->arrears_cents > 0) {
            DisconnectionNotice::create([
                'municipal_account_id' => $arrearsAccount->id, 'property_id' => $arrearsAccount->property_id,
                'arrears_cents' => $arrearsAccount->arrears_cents, 'status' => 'scheduled',
                'notice_date' => today()->subDays(7), 'scheduled_date' => today()->addDays(7),
                'reason' => 'Non-payment of arrears', 'created_by' => $finance->id,
            ]);
        }

        // Water interruptions + schedules
        ServiceInterruption::create(['title' => 'Burst water main', 'type' => 'emergency', 'service' => 'water', 'area' => 'Hillside', 'suburb' => 'Hillside', 'ward' => '8', 'starts_at' => now()->subHours(6), 'expected_restoration_at' => now()->addHours(18), 'reason' => 'Burst 450mm main on Matopos Rd', 'status' => 'active', 'created_by' => $content->id]);
        ServiceInterruption::create(['title' => 'Planned maintenance — Nketa pump station', 'type' => 'planned', 'service' => 'water', 'area' => 'Nketa', 'suburb' => 'Nketa', 'ward' => '15', 'starts_at' => now()->addDays(2), 'expected_restoration_at' => now()->addDays(2)->addHours(8), 'reason' => 'Valve replacement', 'status' => 'planned', 'created_by' => $content->id]);
        ServiceInterruption::create(['title' => 'Pump fault resolved', 'type' => 'emergency', 'service' => 'water', 'area' => 'Pumula South', 'suburb' => 'Pumula South', 'starts_at' => now()->subDays(4), 'resolved_at' => now()->subDays(3), 'reason' => 'Booster pump failure', 'status' => 'resolved', 'created_by' => $content->id]);

        foreach ([['Hillside', 8, 1], ['Nketa', 15, 2], ['Pumula South', 19, 3], ['Suburbs', 2, 5]] as [$suburb, $ward, $dow]) {
            WaterSchedule::create(['area' => $suburb, 'suburb' => $suburb, 'ward' => (string) $ward, 'day_of_week' => $dow, 'start_time' => '05:00', 'end_time' => '17:00', 'effective_from' => now()->subMonths(2), 'status' => 'active', 'created_by' => $content->id]);
        }

        // Refuse schedules
        foreach (['Bulawayo CBD' => 1, 'Suburbs' => 2, 'Hillside' => 3, 'Nketa' => 4, 'Pumula South' => 5, 'Emakhandeni' => 1, 'Belmont' => 2, 'Luveve' => 3] as $suburb => $dow) {
            RefuseSchedule::create(['area' => $suburb, 'suburb' => $suburb, 'collection_day' => $dow, 'frequency' => 'weekly', 'next_collection_date' => now()->next($dow), 'status' => 'active', 'notes' => $suburb === 'Nketa' ? 'Collection moved to Saturday on public holidays.' : null, 'created_by' => $content->id]);
        }

        // Announcements (10)
        $announcements = [
            ['Water tariff review — public consultation', 'billing', 'high'],
            ['Scheduled maintenance: Hillside reservoir', 'water', 'high'],
            ['Festive season refuse collection changes', 'refuse', 'normal'],
            ['Pothole repair programme — Ward 5 & 14', 'roads', 'normal'],
            ['Cholera awareness campaign', 'public_health', 'urgent'],
            ['New online payment options now live', 'billing', 'normal'],
            ['Community clean-up — Nketa', 'community', 'low'],
            ['Emergency contact numbers updated', 'emergency', 'high'],
            ['City sports festival', 'events', 'low'],
            ['Rates clearance certificate process update', 'billing', 'normal'],
        ];
        foreach ($announcements as [$title, $cat, $prio]) {
            Announcement::create([
                'title' => $title,
                'slug' => Str::slug($title).'-'.rand(100, 999),
                'body' => fake()->paragraphs(3, true),
                'category' => $cat, 'priority' => $prio,
                'status' => Announcement::STATUS_PUBLISHED, 'published_at' => now()->subDays(rand(1, 20)),
                'created_by' => $content->id,
            ]);
        }

        // Service requests (10)
        $categories = array_keys(ServiceRequest::CATEGORIES);
        for ($i = 0; $i < 10; $i++) {
            $req = ServiceRequest::create([
                'ticket_number' => 'SR-'.now()->subDays(10 - $i)->format('Ymd').'-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'user_id' => [$resident, $tenant, $owner][$i % 3]->id,
                'property_id' => $properties[$i % count($properties)]->id,
                'category' => $categories[$i % count($categories)],
                'description' => fake()->sentence(12),
                'location_text' => fake()->streetAddress(),
                'priority' => ['low', 'normal', 'high'][$i % 3],
                'status' => ServiceRequest::STATUSES[$i % count(ServiceRequest::STATUSES)],
                'assigned_to' => $i % 2 ? $support->id : null,
            ]);
            ServiceRequestComment::create(['service_request_id' => $req->id, 'user_id' => $req->user_id, 'body' => 'Please attend to this urgently.', 'is_internal' => false]);
        }

        // FAQs
        foreach ([
            ['billing', 'How do I pay my rates?', 'Pay online via the Payments page, or transfer to the council bank account and upload proof of payment.'],
            ['billing', 'Why is my bill estimated?', 'When a meter cannot be read, consumption is estimated and corrected on the next actual reading.'],
            ['water', 'What is water shedding?', 'Scheduled supply interruption by area to manage limited raw water. See the Water page for your schedule.'],
            ['account', 'How do I link a property?', 'Register, then submit your municipal account number under My Properties → Link property. An officer verifies ownership.'],
            ['payments', 'My payment is pending — what now?', 'Bank transfers are reviewed by finance. Gateway payments confirm automatically via provider callback.'],
        ] as $i => [$cat, $q, $a]) {
            Faq::create(['question' => $q, 'answer' => $a, 'category' => $cat, 'sort_order' => $i, 'is_published' => true]);
        }

        // System settings
        SystemSetting::put('site.notice', 'Demo platform — fictional data', 'string');
        SystemSetting::put('billing.currency', 'USD', 'string');
    }
}
