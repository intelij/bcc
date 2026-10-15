<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/** Inbound provider webhooks. Signature-verified, deduplicated, rate-limited. */
class WebhookController extends Controller
{
    public function payment(Request $request, string $provider, PaymentService $service): JsonResponse
    {
        $key = 'webhook:'.$provider.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json(['error' => 'rate limited'], 429);
        }
        RateLimiter::hit($key, 60);

        $hook = $service->handleWebhook($provider, $request);

        return response()->json([
            'status' => $hook->status,
        ], $hook->status === 'failed' ? 422 : 200);
    }
}
