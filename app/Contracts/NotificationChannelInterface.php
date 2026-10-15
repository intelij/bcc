<?php

namespace App\Contracts;

use App\Models\NotificationDelivery;

/**
 * Outbound channel adapter (SMS, WhatsApp, ...). Email uses Laravel's mailer.
 * send() must update the given delivery record and return it.
 */
interface NotificationChannelInterface
{
    public function channel(): string;

    public function send(NotificationDelivery $delivery, string $body, ?string $subject = null): NotificationDelivery;
}
