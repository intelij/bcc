<?php

namespace App\Services\Integrations\Notifications;

use App\Contracts\NotificationChannelInterface;
use App\Models\IntegrationLog;
use App\Models\NotificationDelivery;

/**
 * MOCK WhatsApp provider — writes messages to the mock outbox.
 * NOT a production integration; real adapter targets Meta Cloud API.
 */
class MockWhatsAppProvider implements NotificationChannelInterface
{
    public function channel(): string
    {
        return 'whatsapp';
    }

    public function send(NotificationDelivery $delivery, string $body, ?string $subject = null): NotificationDelivery
    {
        $ref = 'WA-'.strtoupper(bin2hex(random_bytes(6)));

        $dir = storage_path('app/mock-outbox');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir.'/'.$ref.'.json', json_encode([
            'channel' => 'whatsapp', 'to' => $delivery->address,
            'subject' => $subject, 'body' => $body, 'at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        IntegrationLog::create([
            'integration' => 'whatsapp', 'direction' => 'outbound', 'operation' => 'send',
            'status' => 'success',
            'request_summary' => 'to='.($delivery->address ?? 'n/a'),
            'response_summary' => 'mock accepted, ref '.$ref,
            'duration_ms' => 5,
        ]);

        $delivery->forceFill([
            'provider' => 'mock_whatsapp',
            'provider_reference' => $ref,
            'status' => 'delivered',
            'sent_at' => now(),
            'delivered_at' => now(),
        ])->save();

        return $delivery;
    }
}
