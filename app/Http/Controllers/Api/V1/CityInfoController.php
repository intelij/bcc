<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Announcement;
use App\Models\RefuseSchedule;
use App\Models\ServiceInterruption;
use App\Models\WaterSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Public-facing city information, scoped to the resident's suburbs. */
class CityInfoController extends ApiController
{
    public function water(Request $request): JsonResponse
    {
        $suburbs = $request->user()->accessibleProperties()->pluck('suburb')->unique()->filter();

        $relevant = fn ($q) => $q->when($suburbs->isNotEmpty(), fn ($q2) => $q2->where(fn ($q3) => $q3
            ->whereIn('suburb', $suburbs)->orWhereNull('suburb')->orWhere('area', 'All areas')));

        return response()->json([
            'interruptions' => ServiceInterruption::where('service', 'water')
                ->current()->tap($relevant)->get()
                ->map(fn ($i) => $this->interruptionData($i))->values(),
            'recently_resolved' => ServiceInterruption::where('service', 'water')
                ->where('status', 'resolved')->latest('resolved_at')->limit(10)->get()
                ->map(fn ($i) => $this->interruptionData($i))->values(),
            'schedules' => WaterSchedule::where('status', 'active')->tap($relevant)
                ->orderBy('day_of_week')->get()->map(fn ($s) => [
                    'area' => $s->area,
                    'suburb' => $s->suburb,
                    'ward' => $s->ward,
                    'day_of_week' => $s->day_of_week,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'effective_from' => $s->effective_from?->toDateString(),
                    'effective_to' => $s->effective_to?->toDateString(),
                ])->values(),
        ]);
    }

    public function refuse(Request $request): JsonResponse
    {
        $suburbs = $request->user()->accessibleProperties()->pluck('suburb')->unique()->filter();

        $schedules = RefuseSchedule::where('status', '!=', 'suspended')
            ->when($suburbs->isNotEmpty(), fn ($q) => $q->where(fn ($q2) => $q2
                ->whereIn('suburb', $suburbs)->orWhereNull('suburb')))
            ->orderBy('collection_day')->get();

        return response()->json([
            'data' => $schedules->map(fn ($s) => [
                'area' => $s->area,
                'suburb' => $s->suburb,
                'ward' => $s->ward,
                'collection_day' => $s->collection_day,
                'frequency' => $s->frequency,
                'next_collection_date' => $s->next_collection_date?->toDateString(),
                'status' => $s->status,
                'notes' => $s->notes,
            ])->values(),
        ]);
    }

    public function announcements(Request $request): JsonResponse
    {
        $announcements = Announcement::published()
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->latest('published_at')->paginate(15)->withQueryString();

        $announcements->setCollection($announcements->getCollection()->map(fn ($a) => $this->announcementData($a)));

        return response()->json($announcements);
    }

    public function announcement(Announcement $announcement): JsonResponse
    {
        abort_unless(
            $announcement->status === Announcement::STATUS_PUBLISHED || request()->user()?->isStaff(),
            404
        );

        return response()->json(['data' => $this->announcementData($announcement)]);
    }
}
