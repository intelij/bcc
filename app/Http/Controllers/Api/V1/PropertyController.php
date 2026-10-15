<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Property;
use App\Models\PropertyUser;
use App\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends ApiController
{
    /** All property links for the current user (approved and pending). */
    public function index(Request $request): JsonResponse
    {
        $links = $request->user()->propertyUsers()
            ->with('property.account')->latest()->get();

        return response()->json([
            'data' => $links->map(fn (PropertyUser $link) => $this->propertyData($link->property, $link))->values(),
        ]);
    }

    public function show(Property $property): JsonResponse
    {
        $this->authorize('view', $property);
        $property->load('account', 'meters.readings');

        $link = request()->user()->propertyUsers()
            ->where('property_id', $property->id)->first();

        return response()->json([
            'data' => array_merge($this->propertyData($property, $link), [
                'meters' => $property->meters->map(fn ($m) => [
                    'meter_number' => $m->meter_number,
                    'status' => $m->status,
                    'latest_reading' => $m->readings->sortByDesc('reading_date')->first() ? [
                        'reading' => $m->readings->sortByDesc('reading_date')->first()->current_reading,
                        'date' => $m->readings->sortByDesc('reading_date')->first()->reading_date?->toDateString(),
                    ] : null,
                ])->values(),
            ]),
        ]);
    }

    /** Request a property link (pending staff verification). */
    public function link(Request $request, PropertyService $properties): JsonResponse
    {
        $data = $request->validate([
            'account_number' => ['required', 'string', 'max:64'],
            'relationship' => ['required', 'in:owner,tenant'],
        ]);

        $link = $properties->requestLink(
            $request->user(),
            $data['account_number'],
            $data['relationship'] === 'tenant' ? PropertyUser::REL_TENANT : PropertyUser::REL_OWNER
        );

        return response()->json([
            'data' => $this->propertyData($link->property, $link),
            'message' => 'Link request submitted. The council will verify and approve it.',
        ], 201);
    }
}
