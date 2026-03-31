<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\DashboardAnnouncementPostRequest;
use Motor\Admin\Http\Requests\DashboardAnnouncementPutRequest;
use Motor\Admin\Http\Resources\DashboardAnnouncementResource;
use Motor\Admin\Models\DashboardAnnouncement;

class DashboardAnnouncementsController extends ApiController
{
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $announcements = DashboardAnnouncement::visibleTo($user)
            ->with(['creator', 'linkable'])
            ->latest()
            ->get();

        return response()->json([
            'data' => DashboardAnnouncementResource::collection($announcements),
        ]);
    }

    public function store(DashboardAnnouncementPostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (! $user->hasRole('SuperAdmin') && ! $user->can('dashboard-announcements.write')) {
            $data['audience'] = 'self';
            $data['target_user_ids'] = null;
            unset($data['client_id']);
        }

        // Use client_id from request when targeting a client, otherwise fall back to user's first client
        if (empty($data['client_id'])) {
            $data['client_id'] = $user->clients->first()?->id
                ?? config('motor-admin.default_client_id', 1);
        }

        $data['created_by'] = $user->id;

        $announcement = DashboardAnnouncement::create($data);

        return (new DashboardAnnouncementResource($announcement->load(['creator', 'linkable'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(DashboardAnnouncementPutRequest $request, DashboardAnnouncement $announcement): JsonResponse
    {
        $user = $request->user();

        $canWrite = $user->hasRole('SuperAdmin') || $user->can('dashboard-announcements.write');

        if (! $canWrite && $announcement->created_by !== $user->id) {
            abort(403);
        }

        $data = $request->validated();

        if (! $canWrite) {
            $data['audience'] = 'self';
            $data['target_user_ids'] = null;
            unset($data['client_id']);
        }

        $announcement->update($data);

        return (new DashboardAnnouncementResource($announcement->load(['creator', 'linkable'])))
            ->response();
    }

    public function destroy(DashboardAnnouncement $announcement): JsonResponse
    {
        $user = auth()->user();

        if (! $user->can('dashboard-announcements.write') && $announcement->created_by !== $user->id) {
            abort(403);
        }

        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted']);
    }

    public function dismiss(DashboardAnnouncement $announcement): JsonResponse
    {
        $user = auth()->user();

        $announcement->dismissedByUsers()->syncWithoutDetaching([
            $user->id => ['dismissed_at' => now()],
        ]);

        return response()->json(['message' => 'Announcement dismissed']);
    }
}
