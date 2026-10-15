<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public const SUBJECTS = [
        'NEW_BILL' => 'New municipal bill issued',
        'PAYMENT_RECEIVED' => 'Payment received',
        'PAYMENT_FAILED' => 'Payment failed',
        'PAYMENT_PENDING' => 'Payment pending',
        'PAYMENT_REMINDER' => 'Payment reminder',
        'ACCOUNT_UPDATED' => 'Account updated',
        'DISCONNECTION_WARNING' => 'Disconnection warning',
        'DISCONNECTION_SCHEDULED' => 'Disconnection scheduled',
        'SERVICE_RESTORED' => 'Service restored',
        'WATER_INTERRUPTION' => 'Water interruption notice',
        'REFUSE_CHANGE' => 'Refuse collection change',
        'CITY_ANNOUNCEMENT' => 'City announcement',
        'SERVICE_REQUEST_CREATED' => 'Service request received',
        'SERVICE_REQUEST_UPDATED' => 'Service request update',
    ];

    public function run(): void
    {
        foreach (self::SUBJECTS as $event => $subject) {
            foreach (['in_app', 'email', 'sms', 'whatsapp'] as $channel) {
                NotificationTemplate::firstOrCreate(
                    ['event' => $event, 'channel' => $channel],
                    [
                        'subject' => $channel === 'email' ? $subject.' — '.config('city.short_name') : null,
                        // {{message}} is always supplied by NotificationService callers;
                        // richer {{data.*}} placeholders can be added per event later.
                        'body_template' => '{{ message }}',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
