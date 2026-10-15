<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyUser;
use App\Models\User;
use App\Services\PropertyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function index(Request $request): View
    {
        $links = $request->user()->propertyUsers()
            ->with('property.account')
            ->latest()->get();

        return view('properties.index', compact('links'));
    }

    public function create(): View
    {
        return view('properties.create');
    }

    public function store(Request $request, PropertyService $properties): RedirectResponse
    {
        $data = $request->validate([
            'account_number' => ['required', 'string', 'max:64'],
            'relationship' => ['required', 'in:owner,tenant,agent'],
        ]);

        $properties->requestLink($request->user(), $data['account_number'], $data['relationship']);

        return redirect()->route('properties.index')
            ->with('success', 'Link request submitted. A municipal officer will verify it.');
    }

    public function show(Property $property): View
    {
        $this->authorize('view', $property);

        $property->load(['account.bills' => fn ($q) => $q->latest('issue_date')->limit(12), 'meters.latestReading']);

        $link = request()->user()->propertyUsers()
            ->where('property_id', $property->id)->first();

        $tenants = $property->propertyUsers()->with('user')->where('relationship', 'tenant')->get();

        return view('properties.show', compact('property', 'link', 'tenants'));
    }

    public function authorizeTenant(Request $request, Property $property, PropertyService $properties): RedirectResponse
    {
        $this->authorize('manageTenants', $property);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'can_view_bills' => ['boolean'], 'can_view_account' => ['boolean'],
            'can_pay' => ['boolean'], 'can_submit_requests' => ['boolean'],
            'can_view_statements' => ['boolean'],
        ]);

        $tenant = User::where('email', $data['email'])->first();
        if (! $tenant) {
            return back()->withErrors(['email' => 'No registered user with that email. The tenant must register first.']);
        }

        $properties->authorizeTenant($request->user(), $property, $tenant, [
            'can_view_bills' => $request->boolean('can_view_bills'),
            'can_view_account' => $request->boolean('can_view_account'),
            'can_pay' => $request->boolean('can_pay'),
            'can_submit_requests' => $request->boolean('can_submit_requests'),
            'can_view_statements' => $request->boolean('can_view_statements'),
        ]);

        return back()->with('success', 'Tenant access updated.');
    }

    public function revokeLink(Request $request, PropertyUser $link, PropertyService $properties): RedirectResponse
    {
        $user = $request->user();
        $isOwnerOfProperty = $user->propertyUsers()
            ->where('property_id', $link->property_id)
            ->where('relationship', 'owner')->where('status', 'approved')->exists();

        abort_unless($link->user_id === $user->id || $isOwnerOfProperty || $user->hasPermission('properties.verify'), 403);

        $properties->revokeLink($link, $user);

        return back()->with('success', 'Property link revoked.');
    }
}
