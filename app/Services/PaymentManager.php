<?php

namespace App\Services;

use App\Contracts\PaymentProviderInterface;
use InvalidArgumentException;

/** Resolves payment provider adapters from config. */
class PaymentManager
{
    /** @return array<string,PaymentProviderInterface> all configured providers */
    public function all(): array
    {
        $out = [];
        foreach (config('integrations.payments.providers', []) as $key => $cfg) {
            $out[$key] = app($cfg['class']);
        }

        return $out;
    }

    public function driver(?string $key = null): PaymentProviderInterface
    {
        $key ??= config('integrations.payments.default');
        $cfg = config("integrations.payments.providers.$key");

        if (! $cfg) {
            throw new InvalidArgumentException("Unknown payment provider [$key]");
        }

        return app($cfg['class']);
    }
}
