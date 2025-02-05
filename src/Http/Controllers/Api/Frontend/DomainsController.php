<?php

namespace Motor\Admin\Http\Controllers\Api\Frontend;

use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Resources\Frontend\DomainCollection;
use Motor\Admin\Models\Domain;

/**
 * Class DomainsController
 */
class DomainsController extends ApiController
{
    protected string $model = Domain::class;

    /**
     * @OA\Get (
     *   tags={"FrontendDomainsController"},
     *   path="/api/frontend/domains",
     *   summary="Get active domain collection",
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
        return (new DomainCollection(Domain::where('is_active', true)->get()))->additional(['message' => 'Domain collection read']);
    }
}
