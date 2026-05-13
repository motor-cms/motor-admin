<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;

/**
 * Class AdminNavigationsController
 */
class AdminNavigationsController extends ApiController
{
    /**
     * Get all navigation items for the admin frontend
     *
     * Returns a multidimensional array with nested items
     *
     * @response array{data: array{slug: string, icon: string, route: string|null, roles:array{string}, permissions: array{string}, name:string, items:array{slug: string, icon: string, route: string|null, roles:array{string}, permissions: array{string}, aliases: array{string}, name:string}[]}[]}
     */
    public function index(): JsonResponse
    {
        $items = config('motor-admin-navigation.items');
        ksort($items);

        $user = auth()->user();

        if ($user && ! $user->hasRole('SuperAdmin')) {
            $items = $this->filterByPermissions($items, $user);
        }

        return response()->json(['data' => $items]);
    }

    /**
     * Recursively filter navigation items by the user's permissions.
     * Items with empty permissions are always visible.
     * Parent items are removed when no children remain after filtering.
     */
    private function filterByPermissions(array $items, $user): array
    {
        $filtered = [];

        foreach ($items as $key => $item) {
            if (! empty($item['items'])) {
                $item['items'] = $this->filterByPermissions($item['items'], $user);

                if (empty($item['items'])) {
                    continue;
                }

                // Parent group with surviving children — always show as container
                $filtered[$key] = $item;

                continue;
            }

            $permissions = $item['permissions'] ?? [];

            if (empty($permissions) || $user->hasAnyPermission($permissions)) {
                $filtered[$key] = $item;
            }
        }

        return $filtered;
    }
}
