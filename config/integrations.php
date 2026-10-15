<?php

use App\Services\Integrations\Municipal\MockMunicipalBillingProvider;
use App\Services\Integrations\Notifications\MockSmsProvider;
use App\Services\Integrations\Notifications\MockWhatsAppProvider;
use App\Services\Integrations\Payments\BankTransferProvider;
use App\Services\Integrations\Payments\MockPaymentProvider;

return [
    /*
    |----------------------------------------------------------------------
    | Integration drivers — mocks ship with the app; real adapters implement
    | the same interfaces and are selected here via env vars. Everything
    | listed as "mock" is NOT a production integration.
    |----------------------------------------------------------------------
    */
    'payments' => [
        'default' => env('PAYMENT_PROVIDER', 'mock'),
        'providers' => [
            'mock' => [
                'class' => MockPaymentProvider::class,
                'label' => 'DemoPay (mock)',
                'secret' => env('MOCK_PAYMENT_SECRET', 'mock-secret-change-me'),
                'methods' => ['mobile_money', 'card'],
            ],
            'bank_transfer' => [
                'class' => BankTransferProvider::class,
                'label' => 'Bank transfer',
                'methods' => ['bank_transfer'],
            ],
            // 'ecocash' => ['class' => App\Services\Integrations\Payments\EcoCashProvider::class, ...],
            // 'onemoney' => [...], 'paynow' => [...]
        ],
    ],

    'notifications' => [
        'sms' => [
            'driver' => env('SMS_PROVIDER', 'mock'),
            'mock' => ['class' => MockSmsProvider::class],
        ],
        'whatsapp' => [
            'driver' => env('WHATSAPP_PROVIDER', 'mock'),
            'mock' => ['class' => MockWhatsAppProvider::class],
        ],
    ],

    'municipal_billing' => [
        'driver' => env('MUNICIPAL_BILLING_PROVIDER', 'mock'),
        'mock' => ['class' => MockMunicipalBillingProvider::class],
    ],
];
