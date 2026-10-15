<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillController;
use App\Http\Controllers\Api\V1\CityInfoController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\ServiceRequestController;
use App\Http\Controllers\Api\V1\StatementController;
use App\Http\Controllers\AttachmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — consumed by the Flutter app and any future first-party clients.
| Auth: Sanctum personal access tokens (Authorization: Bearer <token>).
| Authorization: identical policies to the web portal — enforced per object.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('meta', [MetaController::class, 'index'])->name('meta');

    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:6,1')->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')->name('auth.login');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:6,1')->name('auth.forgot');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::post('properties/link', [PropertyController::class, 'link'])->name('properties.link');
        Route::get('properties/{property}', [PropertyController::class, 'show'])->name('properties.show');

        Route::get('bills', [BillController::class, 'index'])->name('bills.index');
        Route::get('bills/{bill}', [BillController::class, 'show'])->name('bills.show');

        Route::get('statements/{account}', [StatementController::class, 'show'])->name('statements.show');
        Route::get('statements/{account}/pdf', [StatementController::class, 'pdf'])->name('statements.pdf');

        Route::get('payments/providers', [PaymentController::class, 'providers'])->name('payments.providers');
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments', [PaymentController::class, 'store'])
            ->middleware('throttle:10,1')->name('payments.store');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/proof', [PaymentController::class, 'uploadProof'])->name('payments.proof');

        Route::get('requests', [ServiceRequestController::class, 'index'])->name('requests.index');
        Route::post('requests', [ServiceRequestController::class, 'store'])
            ->middleware('throttle:10,1')->name('requests.store');
        Route::get('requests/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('requests.show');
        Route::post('requests/{serviceRequest}/comments', [ServiceRequestController::class, 'comment'])->name('requests.comment');

        Route::get('water', [CityInfoController::class, 'water'])->name('water');
        Route::get('refuse', [CityInfoController::class, 'refuse'])->name('refuse');
        Route::get('announcements', [CityInfoController::class, 'announcements'])->name('announcements.index');
        Route::get('announcements/{announcement:slug}', [CityInfoController::class, 'announcement'])->name('announcements.show');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::get('preferences', [NotificationController::class, 'preferences'])->name('preferences.index');
        Route::put('preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');

        Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::delete('devices/{deviceId}', [DeviceController::class, 'destroy'])->name('devices.destroy');

        // Authorized private attachment download (same policy as web).
        Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
    });
});
