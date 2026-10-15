<x-app-layout>
    <h1 class="text-xl font-semibold mb-1">Link a property</h1>
    <p class="text-sm text-slate-500 mb-5">Enter the municipal account number from your bill. A municipal officer verifies every link before access is granted.</p>

    <form method="POST" action="{{ route('properties.store') }}" class="max-w-md space-y-4 rounded-xl border border-slate-200 bg-white p-5">
        @csrf
        <div>
            <x-input-label for="account_number" value="Municipal account number" />
            <x-text-input id="account_number" name="account_number" class="mt-1 block w-full" placeholder="BYO-100001" :value="old('account_number')" required />
            <x-input-error :messages="$errors->get('account_number')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="relationship" value="I am the" />
            <select id="relationship" name="relationship" class="mt-1 block w-full rounded-lg border-slate-300">
                <option value="owner">Property owner</option>
                <option value="tenant">Tenant</option>
                <option value="agent">Managing agent</option>
            </select>
        </div>
        <x-primary-button>Submit link request</x-primary-button>
    </form>
</x-app-layout>
