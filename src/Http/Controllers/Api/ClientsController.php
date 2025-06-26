<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\ClientGetRequest;
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
     * List/search all records
     *
     * This will return a paginated response. Some limited search operations are also possible.
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<ClientCollection>>
     */
    public function index(ClientGetRequest $request): ClientCollection
    {
        $paginator = ClientService::collection()
            ->getPaginator();

        return new ClientCollection($paginator)->additional(['message' => 'Client collection read']);
    }

    /**
     * Create record
     */
    public function store(ClientPostRequest $request): JsonResponse
    {
        $result = ClientService::create($request)
            ->getResult();

        return new ClientResource($result)->additional(['message' => 'Client created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(Client $client): ClientResource
    {
        $result = ClientService::show($client)
            ->getResult();

        return new ClientResource($result)->additional(['message' => 'Client read']);
    }

    /**
     * Update record
     */
    public function update(ClientPatchRequest $request, Client $client): ClientResource
    {
        $result = ClientService::update($client, $request)
            ->getResult();

        return new ClientResource($result)->additional(['message' => 'Client updated']);
    }

    /**
     * Delete record
     */
    public function destroy(Client $client): JsonResponse
    {
        $result = ClientService::delete($client)
            ->getResult();

        if ($result) {
            return response()->json(['message' => 'Client deleted']);
        }

        return response()->json(['message' => 'Problem deleting Client'], 404);
    }
}
