<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Full name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="phone" :value="__('Mobile number')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')" required placeholder="+263 7XX XXX XXX" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="national_id" :value="__('National ID / passport (optional)')" />
            <x-text-input id="national_id" class="block mt-1 w-full" type="text" name="national_id" :value="old('national_id')" />
            <x-input-error :messages="$errors->get('national_id')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="user_type" :value="__('I am registering as')" />
            <select id="user_type" name="user_type" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <option value="resident" @selected(old('user_type') === 'resident')>Resident</option>
                <option value="property_owner" @selected(old('user_type') === 'property_owner')>Property owner</option>
                <option value="tenant" @selected(old('user_type') === 'tenant')>Tenant</option>
            </select>
            <x-input-error :messages="$errors->get('user_type')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="account_number" :value="__('Municipal account number (optional)')" />
            <x-text-input id="account_number" class="block mt-1 w-full" type="text" name="account_number" :value="old('account_number')" placeholder="BYO-100001" />
            <p class="text-xs text-gray-500 mt-1">Linking is verified by a municipal officer before access is granted.</p>
            <x-input-error :messages="$errors->get('account_number')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>
            <x-primary-button class="ms-4">{{ __('Register') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
