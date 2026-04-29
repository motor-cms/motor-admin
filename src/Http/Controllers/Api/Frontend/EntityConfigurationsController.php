<?php

namespace Motor\Admin\Http\Controllers\Api\Frontend;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Resources\V2\EntityConfigurationResource;
use Motor\Admin\Models\Domain;
use Motor\Admin\Models\EntityConfiguration;

/**
 * Public endpoint to retrieve entity configurations for a domain.
 *
 * GET /api/frontend/entity-configurations?host=example.com&group=umami
 */
class EntityConfigurationsController extends ApiController
{
    protected string $model = EntityConfiguration::class;

    public function index(Request $request): JsonResponse
    {
        $host = $request->query('host');
        if (! $host) {
            return response()->json(['message' => 'host parameter is required'], 422);
        }

        $domain = Domain::where('host', $host)
            ->where('is_active', true)
            ->first();

        if (! $domain) {
            return response()->json(['message' => 'Domain not found'], 404);
        }

        $query = $domain->entityConfigurations()->with('configVariable');

        if ($group = $request->query('group')) {
            $query->whereHas('configVariable', fn ($q) => $q->where('group', $group));
        }

        if ($package = $request->query('package')) {
            $query->whereHas('configVariable', fn ($q) => $q->where('package', $package));
        }

        $configurations = $query->get();

        return response()->json([
            'data' => EntityConfigurationResource::collection($configurations),
            'meta' => ['message' => 'Entity configurations retrieved'],
        ]);
    }
}
