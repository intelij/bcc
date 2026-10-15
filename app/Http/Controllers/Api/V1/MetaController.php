<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Announcement;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;

/** Client bootstrap metadata — safe to expose without authentication. */
class MetaController extends ApiController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'city' => [
                'name' => config('city.name'),
                'short_name' => config('city.short_name'),
                'tagline' => config('city.tagline'),
                'currency' => config('city.currency'),
                'primary_color' => config('city.primary_color'),
                'support_email' => config('city.support_email'),
                'support_phone' => config('city.support_phone'),
                'logo_url' => asset('bcc.svg'),
            ],
            'request_categories' => ServiceRequest::CATEGORIES,
            'announcement_categories' => Announcement::CATEGORIES,
        ]);
    }
}
