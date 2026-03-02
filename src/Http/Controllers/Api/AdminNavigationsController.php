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
        //$customContentQuery = CustomContentType::where('type', 'page')->get();
        //if (class_exists(CustomContentType::class) && ! $customContentQuery->isEmpty()) {
        //    $navigation_position = 200;
        //    $items[$navigation_position] = [
        //        'slug'        => 'custom-content-type',
        //        'icon'        => 'file',
        //        'route'       => null,
        //        'roles'       => ['SuperAdmin'],
        //        'permissions' => [],
        //        'name'        => 'motor-content-type.content-types.content_types',
        //        'items'       => [],
        //    ];
        //    $customContentQuery->each(function (CustomContentType $content_type) use (&$navigation_position, &$items) {
        //        $items[200]['items'][$navigation_position] = [
        //            'slug'        => $content_type->name,
        //            'icon'        => 'fa fa-plus',
        //            'route'       => 'admin.motor-content-type.'.$content_type->id,
        //            'roles'       => ['SuperAdmin'],
        //            'permissions' => [],
        //            'aliases'     => [],
        //            'name'        => $content_type->name,
        //        ];
        //        $navigation_position++;
        //    });
        //}

        return response()->json(['data' => $items]);
    }
}
