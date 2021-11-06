<?php

namespace Motor\Admin\Http\Controllers\Api;

use Motor\Admin\Http\Controllers\ApiController;

/**
 * Class AdminNavigationsController
 *
 * @package Motor\Admin\Http\Controllers\Api
 */
class AdminNavigationsController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $items = config('motor-admin-navigation.items');
        ksort($items);

        return response()->json(['data' => $items]);
    }
}
