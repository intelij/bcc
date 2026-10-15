<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PropertyUser;
use App\Models\Role;
use App\Models\User;
use App\Services\PropertyService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, PropertyService $properties): RedirectResponse
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

            // Optional property link request at registration — pending staff verification.
            if (! empty($validated['account_number'])) {
                try {
                    $relationship = $validated['user_type'] === User::ROLE_TENANT
                        ? PropertyUser::REL_TENANT : PropertyUser::REL_OWNER;
                    $properties->requestLink($user, $validated['account_number'], $relationship);
                } catch (\Throwable) {
                    // Unknown account numbers don't block registration;
                    // the resident can retry linking from the portal.
                }
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
