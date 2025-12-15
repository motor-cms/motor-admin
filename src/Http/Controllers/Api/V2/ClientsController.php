<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Motor\Admin\Http\Requests\Api\ClientGetRequest;
use Motor\Admin\Http\Requests\Api\ClientPatchRequest;
use Motor\Admin\Http\Requests\Api\ClientPostRequest;
use Motor\Admin\Http\Resources\V2\ClientCollection;
use Motor\Admin\Http\Resources\V2\ClientResource;
use Motor\Admin\Models\Client;
use Motor\Admin\Services\ClientService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags Clients
 */
class ClientsController extends ApiController
{
    protected string $model = Client::class;

    protected string $modelResource = 'client';

    public function index(ClientGetRequest $request): ClientCollection
    {
        $paginator = ClientService::collection()
            ->getPaginator();

        return (new ClientCollection($paginator))
            ->additional(['meta' => ['message' => 'Clients retrieved']]);
    }

    public function show(Client $client): ClientResource
    {
        $result = ClientService::show($client)
            ->getResult();

        return (new ClientResource($result))
            ->additional(['meta' => ['message' => 'Client retrieved']]);
    }

    public function store(ClientPostRequest $request): JsonResponse
    {
        $result = ClientService::create($request)
            ->getResult();

        return (new ClientResource($result))
            ->additional(['meta' => ['message' => 'Client created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(ClientPatchRequest $request, Client $client): ClientResource
    {
        $result = ClientService::update($client, $request)
            ->getResult();

        return (new ClientResource($result))
            ->additional(['meta' => ['message' => 'Client updated']]);
    }

    public function destroy(Client $client): Response
    {
        ClientService::delete($client);

        return $this->noContentResponse();
    }
}
