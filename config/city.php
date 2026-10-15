<?php

return [
    /*
    | Configurable city branding — swap for the deploying municipality.
    */
    'name' => env('CITY_NAME', 'Bulawayo City Council'),
    'short_name' => env('CITY_SHORT_NAME', 'Bulawayo'),
    'tagline' => env('CITY_TAGLINE', 'Digital Municipal Services'),
    'currency' => env('CITY_CURRENCY', 'USD'),
    'primary_color' => env('CITY_PRIMARY_COLOR', '#143E62'),
    'support_email' => env('CITY_SUPPORT_EMAIL', 'support@city.example.co.zw'),
    'support_phone' => env('CITY_SUPPORT_PHONE', '+263 292 000000'),

    /*
    | Bank transfer details shown to residents for manual EFT payments.
    | FICTIONAL demo values — replace per environment.
    */
    'bank' => [
        'bank' => env('CITY_BANK_NAME', 'Demo Merchant Bank'),
        'account_name' => env('CITY_BANK_ACCOUNT_NAME', 'Bulawayo City Council — Revenue'),
        'account_number' => env('CITY_BANK_ACCOUNT_NUMBER', '0000000000'),
        'branch' => env('CITY_BANK_BRANCH', 'Main Branch'),
        'reference_hint' => 'Use your municipal account number as the payment reference.',
    ],
];
