<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\MockCheckoutController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RefuseController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\WaterController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Provider webhooks — signature-authenticated, CSRF-exempt.
Route::post('/webhooks/payments/{provider}', [WebhookController::class, 'payment'])
    ->name('webhooks.payment');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Properties
    Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::get('/properties/link', [PropertyController::class, 'create'])->name('properties.create');
    Route::post('/properties/link', [PropertyController::class, 'store'])->name('properties.store');
    Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
    Route::post('/properties/{property}/tenants', [PropertyController::class, 'authorizeTenant'])->name('properties.tenants');
    Route::delete('/links/{link}', [PropertyController::class, 'revokeLink'])->name('links.destroy');

    // Billing
    Route::get('/bills', [BillController::class, 'index'])->name('bills.index');
    Route::get('/bills/{bill}', [BillController::class, 'show'])->name('bills.show');
    Route::get('/statements', [StatementController::class, 'index'])->name('statements.index');
    Route::get('/statements/{account}', [StatementController::class, 'show'])->name('statements.show');
    Route::get('/statements/{account}/pdf', [StatementController::class, 'pdf'])->name('statements.pdf');
    Route::post('/statements/{account}/email', [StatementController::class, 'email'])->name('statements.email');

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/new', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [PaymentController::class, 'store'])
        ->middleware('throttle:10,1')->name('payments.store');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/proof', [PaymentController::class, 'uploadProof'])->name('payments.proof');

    // Mock provider checkout (demo only — see INTEGRATIONS.md)
    Route::get('/mock/checkout/{payment}', [MockCheckoutController::class, 'show'])->name('mock.checkout.show');
    Route::post('/mock/checkout/{payment}', [MockCheckoutController::class, 'complete'])->name('mock.checkout.complete');

    // City services & info
    Route::get('/updates', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/updates/{announcement:slug}', [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::get('/water', [WaterController::class, 'index'])->name('water.index');
    Route::get('/refuse', [RefuseController::class, 'index'])->name('refuse.index');
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');

    // Service requests
    Route::get('/requests', [ServiceRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [ServiceRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [ServiceRequestController::class, 'store'])
        ->middleware('throttle:10,1')->name('requests.store');
    Route::get('/requests/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{serviceRequest}/comment', [ServiceRequestController::class, 'comment'])->name('requests.comment');

    // Notifications & settings
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Attachments (private disk, policy-checked)
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
 |--------------------------------------------------------------------------
 | Municipal administration portal — staff only, permission-gated.
 |--------------------------------------------------------------------------
 */
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'permission:admin.access'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', [Admin\SearchController::class, 'index'])->name('search');

    Route::middleware('permission:users.view,users.manage')->group(function () {
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
        Route::patch('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    });

    Route::middleware('permission:properties.verify')->group(function () {
        Route::get('/properties', [Admin\PropertyController::class, 'index'])->name('properties.index');
        Route::get('/properties/{property}', [Admin\PropertyController::class, 'show'])->name('properties.show');
        Route::patch('/properties/{property}/status', [Admin\PropertyController::class, 'setStatus'])->name('properties.status');
        Route::post('/links/{link}/review', [Admin\PropertyController::class, 'reviewLink'])->name('links.review');
    });

    Route::middleware('permission:accounts.view')->group(function () {
        Route::get('/accounts', [Admin\AccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/{account}', [Admin\AccountController::class, 'show'])->name('accounts.show');
    });

    Route::middleware('permission:billing.manage')->group(function () {
        Route::get('/billing', [Admin\BillingController::class, 'index'])->name('billing.index');
        Route::get('/billing/create', [Admin\BillingController::class, 'create'])->name('billing.create');
        Route::post('/billing', [Admin\BillingController::class, 'store'])->name('billing.store');
        Route::get('/billing/{bill}', [Admin\BillingController::class, 'show'])->name('billing.show');
        Route::patch('/billing/{bill}/status', [Admin\BillingController::class, 'transition'])->name('billing.status');
        Route::post('/billing/readings', [Admin\BillingController::class, 'storeReading'])->name('billing.readings');
    });

    Route::middleware('permission:payments.view')->group(function () {
        Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [Admin\PaymentController::class, 'show'])->name('payments.show');
        Route::post('/payments/manual', [Admin\PaymentController::class, 'storeManual'])->name('payments.manual');
        Route::post('/payments/{payment}/approve', [Admin\PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/{payment}/reject', [Admin\PaymentController::class, 'reject'])->name('payments.reject');
        Route::post('/payments/{payment}/refund', [Admin\PaymentController::class, 'refund'])->name('payments.refund');
    });

    Route::middleware('permission:payments.reconcile')->group(function () {
        Route::get('/reconciliation', [Admin\ReconciliationController::class, 'index'])->name('reconciliation.index');
        Route::post('/reconciliation/import', [Admin\ReconciliationController::class, 'import'])->name('reconciliation.import');
        Route::post('/reconciliation/{payment}/allocate', [Admin\ReconciliationController::class, 'allocate'])->name('reconciliation.allocate');
    });

    Route::middleware('permission:disconnections.manage')->group(function () {
        Route::get('/disconnections', [Admin\DisconnectionController::class, 'index'])->name('disconnections.index');
        Route::get('/disconnections/create', [Admin\DisconnectionController::class, 'create'])->name('disconnections.create');
        Route::post('/disconnections', [Admin\DisconnectionController::class, 'store'])->name('disconnections.store');
        Route::patch('/disconnections/{notice}/status', [Admin\DisconnectionController::class, 'transition'])->name('disconnections.status');
    });

    Route::middleware('permission:requests.manage')->group(function () {
        Route::get('/requests', [Admin\ServiceRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{serviceRequest}', [Admin\ServiceRequestController::class, 'show'])->name('requests.show');
        Route::patch('/requests/{serviceRequest}', [Admin\ServiceRequestController::class, 'update'])->name('requests.update');
        Route::post('/requests/{serviceRequest}/comment', [Admin\ServiceRequestController::class, 'comment'])->name('requests.comment');
    });

    Route::middleware('permission:content.manage')->group(function () {
        Route::get('/water', [Admin\WaterController::class, 'index'])->name('water.index');
        Route::post('/water/interruptions', [Admin\WaterController::class, 'storeInterruption'])->name('water.interruptions.store');
        Route::patch('/water/interruptions/{interruption}', [Admin\WaterController::class, 'updateInterruption'])->name('water.interruptions.update');
        Route::post('/water/schedules', [Admin\WaterController::class, 'storeSchedule'])->name('water.schedules.store');
        Route::delete('/water/schedules/{schedule}', [Admin\WaterController::class, 'destroySchedule'])->name('water.schedules.destroy');

        Route::get('/refuse', [Admin\RefuseController::class, 'index'])->name('refuse.index');
        Route::post('/refuse', [Admin\RefuseController::class, 'store'])->name('refuse.store');
        Route::patch('/refuse/{schedule}', [Admin\RefuseController::class, 'update'])->name('refuse.update');

        Route::get('/announcements', [Admin\AnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/announcements/create', [Admin\AnnouncementController::class, 'create'])->name('announcements.create');
        Route::post('/announcements', [Admin\AnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('/announcements/{announcement}/edit', [Admin\AnnouncementController::class, 'edit'])->name('announcements.edit');
        Route::patch('/announcements/{announcement}', [Admin\AnnouncementController::class, 'update'])->name('announcements.update');
        Route::post('/announcements/{announcement}/publish', [Admin\AnnouncementController::class, 'publish'])->name('announcements.publish');
        Route::delete('/announcements/{announcement}', [Admin\AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });

    Route::middleware('permission:notifications.send')->group(function () {
        Route::get('/notifications', [Admin\NotificationAdminController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/send', [Admin\NotificationAdminController::class, 'send'])->name('notifications.send');
        Route::patch('/notifications/templates/{template}', [Admin\NotificationAdminController::class, 'updateTemplate'])->name('notifications.templates.update');
    });

    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [Admin\ReportController::class, 'show'])->name('reports.show');
    });

    Route::middleware('permission:integrations.manage')->group(function () {
        Route::get('/integrations', [Admin\IntegrationController::class, 'index'])->name('integrations.index');
        Route::post('/integrations/sync', [Admin\IntegrationController::class, 'sync'])->name('integrations.sync');
    });

    Route::middleware('permission:audit.view')->group(function () {
        Route::get('/audit', [Admin\AuditController::class, 'index'])->name('audit.index');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [Admin\SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/faqs', [Admin\SettingsController::class, 'storeFaq'])->name('settings.faqs.store');
        Route::delete('/settings/faqs/{faq}', [Admin\SettingsController::class, 'destroyFaq'])->name('settings.faqs.destroy');
    });
});

require __DIR__.'/auth.php';
