<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Kalnoy\Nestedset\NestedSet;
use Motor\Admin\Http\Requests\Api\CategoryGetRequest;
use Motor\Admin\Http\Resources\V2\CategoryCollection;
use Motor\Admin\Models\Category;
use Motor\Admin\Services\CategoryService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

class FlatCategoriesController extends ApiController
{
    protected string $model = Category::class;

    protected string $modelResource = 'category';

    public function index(CategoryGetRequest $request): CategoryCollection
    {
        $service = CategoryService::collection();
        $service->setSorting([NestedSet::LFT, 'ASC']);
        $paginator = $service->getPaginator();

        return (new CategoryCollection($paginator))
            ->additional(['meta' => ['message' => 'Categories retrieved']]);
    }
}
