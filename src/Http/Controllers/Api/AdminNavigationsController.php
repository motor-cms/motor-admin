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
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $items = config('motor-admin-navigation.items');
        ksort($items);

        return response()->json(['data' => $items]);
    }
}
