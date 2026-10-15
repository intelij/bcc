<?php

if (! function_exists('money')) {
    /** Format integer cents as a currency string. */
    function money(?int $cents, string $currency = 'USD'): string
    {
        $sign = $cents < 0 ? '-' : '';

        return $sign.$currency.' '.number_format(abs($cents ?? 0) / 100, 2);
    }
}

if (! function_exists('status_label')) {
    function status_label(?string $status): string
    {
        return ucwords(str_replace('_', ' ', (string) $status));
    }
}
