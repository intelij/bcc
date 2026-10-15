<?php

namespace App\Services;

use App\Contracts\NotificationChannelInterface;
use App\Mail\GenericNotification;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Central notification engine. Callers raise an EVENT with context;
 * the engine resolves templates + per-user channel preferences,
 * records an in-app notification, and dispatches channel deliveries.
 */
class NotificationService
{
    public const EVENTS = [
        'NEW_BILL', 'PAYMENT_RECEIVED', 'PAYMENT_FAILED', 'PAYMENT_PENDING',
        'PAYMENT_REMINDER', 'ACCOUNT_UPDATED', 'DISCONNECTION_WARNING',
        'DISCONNECTION_SCHEDULED', 'SERVICE_RESTORED', 'WATER_INTERRUPTION',
        'REFUSE_CHANGE', 'CITY_ANNOUNCEMENT', 'SERVICE_REQUEST_CREATED',
        'SERVICE_REQUEST_UPDATED',
    ];

    public const CHANNELS = ['in_app', 'email', 'sms', 'whatsapp'];

    /** Channels enabled by default when a user has no stored preference. */
    private const DEFAULTS = ['in_app' => true, 'email' => true, 'sms' => false, 'whatsapp' => false];

    /**
     * @param  User|iterable<User>  $users
     */
    public function notify(User|iterable $users, string $event, array $context = []): void
    {
        $users = $users instanceof User ? [$users] : $users;

        foreach ($users as $user) {
            $this->notifyUser($user, $event, $context);
        }
    }

    public function notifyUser(User $user, string $event, array $context = []): ?Notification
    {
        $context = ['user' => ['name' => $user->name]] + $context;

        $title = $this->renderSubject($event, $context);
        $body = $this->render($event, 'in_app', $context) ?? ($context['message'] ?? $title);

        $notification = null;
        if ($this->enabled($user, $event, 'in_app')) {
            $notification = Notification::create([
                'user_id' => $user->id, 'event' => $event,
                'title' => $title, 'body' => $body,
                'data' => $context['data'] ?? null,
            ]);
        }

        foreach (['email', 'sms', 'whatsapp'] as $channel) {
            if (! $this->enabled($user, $event, $channel)) {
                continue;
            }
            $templateBody = $this->render($event, $channel, $context);
            if ($templateBody === null) {
                continue; // no template → channel not configured for this event
            }
            $delivery = NotificationDelivery::create([
                'notification_id' => $notification?->id,
                'user_id' => $user->id,
                'event' => $event,
                'channel' => $channel,
                'address' => $channel === 'email' ? $user->email : $user->phone,
                'status' => 'queued',
            ]);
            $this->deliver($delivery, $templateBody, $title);
        }

        return $notification;
    }

    /** Deliver synchronously; extract to a queued job in production. */
    protected function deliver(NotificationDelivery $delivery, string $body, string $subject): void
    {
        try {
            match ($delivery->channel) {
                'email' => $this->deliverEmail($delivery, $body, $subject),
                'sms', 'whatsapp' => $this->channelDriver($delivery->channel)->send($delivery, $body, $subject),
                default => null,
            };
        } catch (\Throwable $e) {
            $delivery->forceFill(['status' => 'failed', 'error' => substr($e->getMessage(), 0, 1000)])->save();
            report($e);
        }
    }

    protected function deliverEmail(NotificationDelivery $delivery, string $body, string $subject): void
    {
        if (! $delivery->address) {
            $delivery->forceFill(['status' => 'skipped', 'error' => 'no email address'])->save();

            return;
        }
        Mail::to($delivery->address)->send(new GenericNotification($subject, $body));
        $delivery->forceFill([
            'provider' => config('mail.default', 'log'),
            'status' => 'sent', 'sent_at' => now(),
        ])->save();
    }

    protected function channelDriver(string $channel): NotificationChannelInterface
    {
        $driver = config("integrations.notifications.$channel.driver", 'mock');
        $class = config("integrations.notifications.$channel.$driver.class");

        return app($class);
    }

    public function enabled(User $user, string $event, string $channel): bool
    {
        $row = $user->notificationPreferences()
            ->where('event', $event)->where('channel', $channel)->first();

        if ($row) {
            return $row->enabled;
        }

        // event-level wildcard prefs
        $row = $user->notificationPreferences()
            ->where('event', '*')->where('channel', $channel)->first();

        return $row ? $row->enabled : (self::DEFAULTS[$channel] ?? false);
    }

    protected function render(string $event, string $channel, array $context): ?string
    {
        $template = NotificationTemplate::where('event', $event)
            ->where('channel', $channel)->where('is_active', true)->first();

        return $template?->render($context);
    }

    protected function renderSubject(string $event, array $context): string
    {
        $template = NotificationTemplate::where('event', $event)
            ->where('channel', 'email')->where('is_active', true)->first();

        if ($template && $template->subject) {
            return preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/',
                fn ($m) => (string) data_get($context, $m[1], ''), $template->subject);
        }

        return $context['title'] ?? ucwords(strtolower(str_replace('_', ' ', $event)));
    }
}
