<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\ContentType\Models\CustomContentType;

/**
 * Class AdminNavigationsController
 */
class AdminNavigationsController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $items = config('motor-admin-navigation.items');
        ksort($items);
        if (class_exists(CustomContentType::class)) {
            $navigation_position = 200;
            $items[$navigation_position] = [
                'slug' => 'custom-content-type',
                'icon' => 'file',
                'route' => null,
                'roles' => ['SuperAdmin'],
                'permissions' => [],
                'name' => 'motor-content-type.content-types.content_types',
                'items' => []
            ];
            CustomContentType::all()->each(function (CustomContentType $content_type) use (&$navigation_position, &$items) {
                $items[200]['items'][$navigation_position] = [
                    'slug' => $content_type->name,
                    'icon' => 'fa fa-plus',
                    'route' => 'admin.motor-content-type.' . $content_type->id,
                    'roles' => ['SuperAdmin'],
                    'permissions' => [],
                    'aliases' => [],
                    'name' => $content_type->name,
                ];
                $navigation_position++;
            });
        }
        return response()->json(['data' => $items]);
    }
}
