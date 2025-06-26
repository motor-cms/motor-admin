<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\DomainGetRequest;
use Motor\Admin\Http\Requests\Api\DomainPatchRequest;
use Motor\Admin\Http\Requests\Api\DomainPostRequest;
use Motor\Admin\Http\Resources\DomainCollection;
use Motor\Admin\Http\Resources\DomainResource;
use Motor\Admin\Models\Domain;
use Motor\Admin\Services\DomainService;

/**
 * Class DomainsController
 */
class DomainsController extends ApiController
{
    protected string $model = Domain::class;

    protected string $modelResource = 'domain';

    /**
     * List/search all records
     *
     * This will return a paginated response. Some limited search operations are also possible.
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<DomainCollection>>
     */
    public function index(DomainGetRequest $request): DomainCollection
    {
        $paginator = DomainService::collection()
            ->getPaginator();

        return new DomainCollection($paginator)->additional(['message' => 'Domain collection read']);
    }

    /**
     * Create record
     */
    public function store(DomainPostRequest $request): JsonResponse
    {
        $result = DomainService::create($request)
            ->getResult();

        return new DomainResource($result)->additional(['message' => 'Domain created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(Domain $domain): DomainResource
    {
        $result = DomainService::show($domain)
            ->getResult();

        return new DomainResource($result)->additional(['message' => 'Domain read']);
    }

    /**
     * Update record
     */
    public function update(DomainPatchRequest $request, Domain $domain): DomainResource
    {
        $result = DomainService::update($domain, $request)
            ->getResult();

        return new DomainResource($result)->additional(['message' => 'Domain updated']);
    }

    /**
     * Delete record
     */
    public function destroy(Domain $domain): JsonResponse
    {
        $result = DomainService::delete($domain)
            ->getResult();

        if ($result) {
            return response()->json(['message' => 'Domain deleted']);
        }

        return response()->json(['message' => 'Problem deleting Domain'], 404);
    }
}
