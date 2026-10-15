<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\PropertyUser;
use App\Models\Role;
use App\Models\User;
use App\Services\PropertyService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;

/** Token auth for API clients (Flutter app, third-party consumers). */
class AuthController extends ApiController
{
    public function register(Request $request, PropertyService $properties): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:32'],
            'national_id' => ['nullable', 'string', 'max:64'],
            'user_type' => ['required', 'in:resident,property_owner,tenant'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()->min(8)->mixedCase()->numbers()],
        ]);

        $user = DB::transaction(function () use ($validated, $properties) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'national_id' => $validated['national_id'] ?? null,
                'password' => Hash::make($validated['password']),
                'status' => User::STATUS_ACTIVE,
            ]);

            $role = Role::where('slug', $validated['user_type'])->first()
                ?? Role::where('slug', User::ROLE_RESIDENT)->first();
            $user->roles()->attach($role);

            if (! empty($validated['account_number'])) {
                try {
                    $properties->requestLink(
                        $user,
                        $validated['account_number'],
                        $validated['user_type'] === User::ROLE_TENANT
                            ? PropertyUser::REL_TENANT : PropertyUser::REL_OWNER
                    );
                } catch (\Throwable) {
                    // Unknown account numbers don't block registration.
                }
            }

            return $user;
        });

        event(new Registered($user));

        return response()->json([
            'token' => $user->createToken($request->string('device_name', 'mobile')->value())->plainTextToken,
            'user' => $this->userData($user->load('roles.permissions')),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $deviceName = $request->validate(['device_name' => ['nullable', 'string', 'max:64']])['device_name'] ?? 'mobile';

        $user = $request->user();

        return response()->json([
            'token' => $user->createToken($deviceName)->plainTextToken,
            'user' => $this->userData($user->load('roles.permissions')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userData($request->user()->load('roles.permissions'))]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Never reveal whether the address exists.
        return response()->json(['message' => 'If that address is registered, a reset link has been sent.']);
    }
}
