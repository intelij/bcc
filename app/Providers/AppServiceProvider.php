<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\Bill;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Models\Property;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Policies\AccountPolicy;
use App\Policies\AttachmentPolicy;
use App\Policies\BillPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\ServiceRequestPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Explicit map — policy names don't always mirror model names.
        Gate::policy(MunicipalAccount::class, AccountPolicy::class);
        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(Bill::class, BillPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(ServiceRequest::class, ServiceRequestPolicy::class);
        Gate::policy(Attachment::class, AttachmentPolicy::class);

        // Permission-slug abilities (e.g. @can('payments.reconcile')) resolve
        // through the role→permission matrix.
        Gate::before(function (User $user, string $ability) {
            if (str_contains($ability, '.')) {
                return $user->hasPermission($ability) ? true : null;
            }

            return null;
        });

        Paginator::useTailwind();
    }
}
