<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Push-notification device registration. Each install registers with a
 * stable client-generated `device_id`; re-registering refreshes the token.
 */
class DeviceController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->devices()->latest('last_seen_at')->get()->map(fn (Device $d) => [
                'device_id' => $d->device_id,
                'platform' => $d->platform,
                'app_version' => $d->app_version,
                'status' => $d->status,
                'last_seen_at' => $d->last_seen_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /** Register or refresh a device + push token. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:128'],
            'platform' => ['required', 'in:android,ios,web'],
            'push_token' => ['nullable', 'string', 'max:512'],
            'app_version' => ['nullable', 'string', 'max:32'],
        ]);

        $device = $request->user()->devices()->updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'platform' => $data['platform'],
                'push_token' => $data['push_token'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'status' => Device::STATUS_ACTIVE,
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['data' => ['device_id' => $device->device_id]], 201);
    }

    /** Revoke a device (e.g. on logout or uninstall). */
    public function destroy(Request $request, string $deviceId): JsonResponse
    {
        $request->user()->devices()
            ->where('device_id', $deviceId)
            ->update(['status' => Device::STATUS_REVOKED, 'push_token' => null]);

        return response()->json(['message' => 'Device revoked.']);
    }
}
