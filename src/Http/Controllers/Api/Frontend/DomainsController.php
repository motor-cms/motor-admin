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
     * List all active domains
     */
    public function index(): DomainCollection
    {
        return new DomainCollection(Domain::where('is_active', true)->get())->additional(['message' => 'Domain collection read']);
    }
}
