<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Motor\Admin\Http\Requests\Api\DomainGetRequest;
use Motor\Admin\Http\Requests\Api\DomainPatchRequest;
use Motor\Admin\Http\Requests\Api\DomainPostRequest;
use Motor\Admin\Http\Resources\V2\DomainCollection;
use Motor\Admin\Http\Resources\V2\DomainResource;
use Motor\Admin\Models\Domain;
use Motor\Admin\Services\DomainService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags Domains
 */
class DomainsController extends ApiController
{
    protected string $model = Domain::class;

    protected string $modelResource = 'domain';

    public function index(DomainGetRequest $request): DomainCollection
    {
        $paginator = DomainService::collection()
            ->getPaginator();

        return (new DomainCollection($paginator))
            ->additional(['meta' => ['message' => 'Domains retrieved']]);
    }

    public function show(Domain $domain): DomainResource
    {
        $result = DomainService::show($domain)
            ->getResult();

        return (new DomainResource($result))
            ->additional(['meta' => ['message' => 'Domain retrieved']]);
    }

    public function store(DomainPostRequest $request): JsonResponse
    {
        $result = DomainService::create($request)
            ->getResult();

        return (new DomainResource($result))
            ->additional(['meta' => ['message' => 'Domain created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(DomainPatchRequest $request, Domain $domain): DomainResource
    {
        $result = DomainService::update($domain, $request)
            ->getResult();

        return (new DomainResource($result))
            ->additional(['meta' => ['message' => 'Domain updated']]);
    }

    public function destroy(Domain $domain): Response
    {
        DomainService::delete($domain);

        return $this->noContentResponse();
    }
}
