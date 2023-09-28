<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\DomainPatchRequest;
use Motor\Admin\Http\Requests\Api\DomainPostRequest;
use Motor\Admin\Http\Resources\DomainCollection;
use Motor\Admin\Http\Resources\DomainResource;
use Motor\Admin\Models\Domain;
use Motor\Admin\Services\DomainService;
use OpenApi\Annotations as OA;

/**
 * Class DomainsController
 */
class DomainsController extends ApiController
{
    protected string $model = Domain::class;

    protected string $modelResource = 'domain';

    /**
     * @OA\Get (
     *   tags={"DomainsController"},
     *   path="/api/domains",
     *   summary="Get domain collection",
     *   security={ {"sanctum": {} }},
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *
     *         @OA\Items(ref="#/components/schemas/DomainResource")
     *       ),
     *
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
     *
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   )
     * )
     *
     * Display a listing of the resource.
     */
    public function index(): DomainCollection
    {
        $paginator = DomainService::collection()
            ->getPaginator();

        return (new DomainCollection($paginator))->additional(['message' => 'Domain collection read']);
    }

    /**
     * @OA\Post (
     *   tags={"DomainsController"},
     *   path="/api/domains",
     *   summary="Create new domain",
     *
     *   @OA\RequestBody(
     *
     *     @OA\JsonContent(ref="#/components/schemas/DomainPostRequest")
     *   ),
     *   security={ {"sanctum": {} }},
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/DomainResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Domain created"
     *       )
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Store a newly created resource in storage.
     */
    public function store(DomainPostRequest $request): JsonResponse
    {
        $result = DomainService::create($request)
            ->getResult();

        return (new DomainResource($result))->additional(['message' => 'Domain created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get (
     *   tags={"DomainsController"},
     *   path="/api/domains/{domain}",
     *   summary="Get single domain",
     *   security={ {"sanctum": {} }},
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="domain",
     *     parameter="domain",
     *     description="Domain id"
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/DomainResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Domain read"
     *       )
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Display the specified resource.
     */
    public function show(Domain $domain): DomainResource
    {
        $result = DomainService::show($domain)
            ->getResult();

        return (new DomainResource($result))->additional(['message' => 'Domain read']);
    }

    /**
     * @OA\Put (
     *   tags={"DomainsController"},
     *   path="/api/domains/{domain}",
     *   summary="Update an existing domain",
     *
     *   @OA\RequestBody(
     *
     *     @OA\JsonContent(ref="#/components/schemas/DomainPatchRequest")
     *   ),
     *   security={ {"sanctum": {} }},
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="domain",
     *     parameter="domain",
     *     description="Domain id"
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/DomainResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Domain updated"
     *       )
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Update the specified resource in storage.
     */
    public function update(DomainPatchRequest $request, Domain $domain): DomainResource
    {
        $result = DomainService::update($domain, $request)
            ->getResult();

        return (new DomainResource($result))->additional(['message' => 'Domain updated']);
    }

    /**
     * @OA\Delete (
     *   tags={"DomainsController"},
     *   path="/api/domains/{domain}",
     *   summary="Delete a domain",
     *   security={ {"sanctum": {} }},
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="domain",
     *     parameter="domain",
     *     description="Domain id"
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Domain deleted"
     *       )
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   ),
     *
     *   @OA\Response(
     *     response="400",
     *     description="Bad request",
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Problem deleting domain"
     *       )
     *     )
     *   )
     * )
     *
     * Remove the specified resource from storage.
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
