<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Resources\DashboardAnnouncementResource;
use Motor\Admin\Models\DashboardAnnouncement;
use Motor\Admin\Models\User;
use Motor\Builder\Models\BuilderPage;
use Motor\Builder\Models\Navigation;
use Motor\Builder\Models\PublishingTime;
use Motor\Media\Models\File;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends ApiController
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $stats = [
            'pages_total' => BuilderPage::where('is_current', true)->count(),
            'pages_draft' => BuilderPage::where('is_current', true)->where('is_published', false)->count(),
            'pages_published' => BuilderPage::where('is_current', true)->where('is_published', true)->count(),
            'pages_scheduled' => PublishingTime::where('is_published', false)
                ->where('to_be_published_at', '>', now())
                ->count(),
            'media_total' => File::count(),
            'navigation_trees' => Navigation::count(),
        ];

        $perPage = min((int) request('activity_per_page', 10), 50);
        $activityPage = max((int) request('activity_page', 1), 1);

        $activityPaginator = Activity::with(['causer', 'subject'])
            ->latest()
            ->paginate($perPage, ['*'], 'activity_page', $activityPage);

        $activities = $activityPaginator->getCollection()
            ->map(function (Activity $activity) {
                $subject = $activity->subject;
                $props = $activity->properties;
                $subjectName = $subject?->name
                    ?? $subject?->title
                    ?? $subject?->description
                    ?? data_get($props, 'attributes.name')
                    ?? data_get($props, 'attributes.title')
                    ?? data_get($props, 'attributes.description')
                    ?? data_get($props, 'old.name')
                    ?? data_get($props, 'old.title')
                    ?? data_get($props, 'old.description')
                    ?? null;

                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'subject_type' => class_basename($activity->subject_type),
                    'subject_id' => $activity->subject_id,
                    'subject_name' => $subjectName,
                    'subject_exists' => $activity->subject !== null,
                    'causer_name' => $activity->causer?->name,
                    'created_at' => $activity->created_at?->toISOString(),
                ];
            });

        $publishingQueue = PublishingTime::with('publishable')
            ->where('is_published', false)
            ->where('to_be_published_at', '>', now())
            ->orderBy('to_be_published_at')
            ->get()
            ->map(function (PublishingTime $pt) {
                return [
                    'id' => $pt->id,
                    'name' => $pt->publishable?->name ?? $pt->publishable?->title ?? 'Unknown',
                    'to_be_published_at' => $pt->to_be_published_at instanceof \DateTimeInterface
                        ? $pt->to_be_published_at->toISOString()
                        : $pt->to_be_published_at,
                    'publishable_type' => class_basename($pt->publishable_type),
                    'publishable_id' => $pt->publishable_id,
                ];
            });

        $announcements = DashboardAnnouncement::visibleTo($user)
            ->with(['creator', 'linkable'])
            ->latest()
            ->get();

        return response()->json([
            'data' => [
                'stats' => $stats,
                'activity' => $activities,
                'activity_meta' => [
                    'current_page' => $activityPaginator->currentPage(),
                    'last_page' => $activityPaginator->lastPage(),
                    'per_page' => $activityPaginator->perPage(),
                    'total' => $activityPaginator->total(),
                ],
                'publishing_queue' => $publishingQueue,
                'announcements' => DashboardAnnouncementResource::collection($announcements),
            ],
        ]);
    }
}
