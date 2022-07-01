<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\ClientPatchRequest;
use Motor\Admin\Http\Requests\Api\ClientPostRequest;
use Motor\Admin\Http\Resources\ClientCollection;
use Motor\Admin\Http\Resources\ClientResource;
use Motor\Admin\Models\Client;
use Motor\Admin\Services\ClientService;

/**
 * Class ClientsController
 */
class ClientsController extends ApiController
{
    protected string $model = Client::class;

    protected string $modelResource = 'client';

    /**
     * @OA\Get (
     *   tags={"ClientsController"},
     *   path="/api/clients",
     *   summary="Get client collection",
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
     *         @OA\Items(ref="#/components/schemas/ClientResource")
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
     * @return ClientCollection
     */
    public function index(): ClientCollection
    {
        $paginator = ClientService::collection()
                                  ->getPaginator();

        return (new ClientCollection($paginator))->additional(['message' => 'Client collection read']);
    }

    /**
     * @OA\Post (
     *   tags={"ClientsController"},
     *   path="/api/clients",
     *   summary="Create new client",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/ClientPostRequest")
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
     *         ref="#/components/schemas/ClientResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Client created"
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
     * @param  ClientPostRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ClientPostRequest $request): JsonResponse
    {
        $result = ClientService::create($request)
                               ->getResult();

        return (new ClientResource($result))->additional(['message' => 'Client created'])
                                            ->response()
                                            ->setStatusCode(201);
    }

    /**
     * @OA\Get (
     *   tags={"ClientsController"},
     *   path="/api/clients/{client}",
     *   summary="Get single client",
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
     *     name="client",
     *     parameter="client",
     *     description="Client id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/ClientResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Client read"
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
     * @param  Client  $record
     * @return ClientResource
     */
    public function show(Client $record): ClientResource
    {
        $result = ClientService::show($record)
                               ->getResult();

        return (new ClientResource($result))->additional(['message' => 'Client read']);
    }

    /**
     * @OA\Put (
     *   tags={"ClientsController"},
     *   path="/api/clients/{client}",
     *   summary="Update an existing client",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/ClientPatchRequest")
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
     *     name="client",
     *     parameter="client",
     *     description="Client id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/ClientResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Client updated"
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
     * @param  ClientPatchRequest  $request
     * @param  Client  $record
     * @return ClientResource
     */
    public function update(ClientPatchRequest $request, Client $record): ClientResource
    {
        $result = ClientService::update($record, $request)
                               ->getResult();

        return (new ClientResource($result))->additional(['message' => 'Client updated']);
    }

    /**
     * @OA\Delete (
     *   tags={"ClientsController"},
     *   path="/api/clients/{client}",
     *   summary="Delete a client",
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
     *     name="client",
     *     parameter="client",
     *     description="Client id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Client deleted"
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
     *         example="Problem deleting client"
     *       )
     *     )
     *   )
     * )
     *
     * Remove the specified resource from storage.
     *
     * @param  Client  $record
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Client $record): JsonResponse
    {
        $result = ClientService::delete($record)
                               ->getResult();

        if ($result) {
            return response()->json(['message' => 'Client deleted']);
        }

        return response()->json(['message' => 'Problem deleting Client'], 404);
    }
}
