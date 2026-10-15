<?php

namespace App\Services\Integrations\Notifications;

use App\Contracts\NotificationChannelInterface;
use App\Models\IntegrationLog;
use App\Models\NotificationDelivery;

/**
 * MOCK SMS provider — writes each message to storage/app/mock-outbox so the
 * demo can show what would have been sent. NOT a production integration.
 */
class MockSmsProvider implements NotificationChannelInterface
{
    public function channel(): string
    {
        return 'sms';
    }

    public function send(NotificationDelivery $delivery, string $body, ?string $subject = null): NotificationDelivery
    {
        $ref = 'SMS-'.strtoupper(bin2hex(random_bytes(6)));

        $dir = storage_path('app/mock-outbox');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir.'/'.$ref.'.json', json_encode([
            'channel' => 'sms', 'to' => $delivery->address, 'body' => $body, 'at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        IntegrationLog::create([
            'integration' => 'sms', 'direction' => 'outbound', 'operation' => 'send',
            'status' => 'success',
            'request_summary' => 'to='.($delivery->address ?? 'n/a'),
            'response_summary' => 'mock accepted, ref '.$ref,
            'duration_ms' => 5,
        ]);

        $delivery->forceFill([
            'provider' => 'mock_sms',
            'provider_reference' => $ref,
            'status' => 'delivered',
            'sent_at' => now(),
            'delivered_at' => now(),
        ])->save();

        return $delivery;
    }
}
