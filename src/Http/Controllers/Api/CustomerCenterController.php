<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\CustomerCenterPostRequest;
use Motor\Admin\Http\Resources\CustomerCenterResource;
use Motor\Admin\Models\CustomerCenter;
use Motor\Admin\Http\Resources\CustomerCenterCollection;
use Motor\Admin\Http\Requests\Api\CustomerCenterPatchRequest;
use Motor\Admin\Services\CustomerCenterService;

/**
 * Class CustomerCenterController
 * @package Motor\Assistant\Http\Controllers\Api
 */
class CustomerCenterController extends ApiController
{

    protected string $model = CustomerCenter::class;

    protected string $modelResource = 'customer_center';

    /**
     * @OA\Get (
     *   tags={"CustomerCenterController"},
     *   path="/api/customer_centers",
     *   summary="Get customer_centers collection",
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/CustomerCenterResource")
     *       ),
     *       @OA\Property(
     *         property="meta",
     *         ref="#/components/schemas/PaginationMeta"
     *       ),
     *       @OA\Property(
     *         property="links",
     *         ref="#/components/schemas/PaginationLinks"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Collection read"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   )
     * )
     *
     * Display a listing of the resource.
     *
     * @return CustomerCenterCollection
     */
    public function index(): CustomerCenterCollection
    {
        $paginator = CustomerCenterService::collection()
                                     ->getPaginator();

        return (new CustomerCenterCollection($paginator))->additional(['message' => 'Customer Center collection read']);
    }

    /**
     * @OA\Post (
     *   tags={"CustomerCenterController"},
     *   path="/api/assistants",
     *   summary="Create new customer_centers",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/CustomerCenterPostRequest")
     *   ),
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/CustomerCenterResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Cusomter Center created"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Store a newly created resource in storage.
     *
     * @param CustomerCenterPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CustomerCenterPostRequest $request): JsonResponse
    {
        $result = CustomerCenterService::create($request)->getResult();

        return (new CustomerCenterResource($result))->additional(['message' => 'Customer Center created'])
                                               ->response()
                                               ->setStatusCode(201);
    }


    /**
     * @OA\Get (
     *   tags={"CustomerCenterController"},
     *   path="/api/assistants/{assistant}",
     *   summary="Get single customer_center",
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="customer_center",
     *     parameter="customer_center",
     *     description="customer_center id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/CustomerCenterResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Cusomter Center read"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Display the specified resource.
     *
     * @param CustomerCenter $customer_center
     * @return CustomerCenterResource
     */
    public function show(CustomerCenter $customer_center): CustomerCenterResource
    {
        return (new CustomerCenterResource($customer_center))->additional(['message' => 'CustomerCenter read']);
    }


    /**
     * @OA\Put (
     *   tags={"CustomerCenterController"},
     *   path="/api/assistants/{assistant}",
     *   summary="Update an existing assistant",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/CustomerCenterPatchRequest")
     *   ),
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="customer_center",
     *     parameter="customer_center",
     *     description="customer_center id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/CustomerCenterResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Customer Center updated"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Update the specified resource in storage.
     *
     * @param CustomerCenterPatchRequest $request
     * @param CustomerCenter        $customer_center
     * @return CustomerCenterResource
     */
    public function update(CustomerCenterPatchRequest $request, CustomerCenter $customer_center): CustomerCenterResource
    {
        $result = CustomerCenterService::update($customer_center, $request)->getResult();

        return (new CustomerCenterResource($result))->additional(['message' => 'Customer Center updated']);
    }


    /**
     * @OA\Delete (
     *   tags={"CustomerCenterController"},
     *   path="/api/assistants/{assistant}",
     *   summary="Delete a Customer Center",
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="customer_center",
     *     parameter="customer_center",
     *     description="customer_center id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Cusomter Center deleted"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   ),
     *   @OA\Response(
     *     response="400",
     *     description="Bad request",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Problem deleting customer_center"
     *       )
     *     )
     *   )
     * )
     *
     * Remove the specified resource from storage.
     *
     * @param CustomerCenter $customer_center
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(CustomerCenter $customer_center): JsonResponse
    {
        $result = $customer_center->delete();

        if ($result) {
            return response()->json(['message' => 'Customer Center deleted']);
        }
        return response()->json(['message' => 'Problem deleting Customer Center'], 404);
    }
}
