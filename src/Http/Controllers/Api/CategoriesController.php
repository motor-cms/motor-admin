<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Kalnoy\Nestedset\NestedSet;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\CategoryGetRequest;
use Motor\Admin\Http\Requests\Api\CategoryPatchRequest;
use Motor\Admin\Http\Requests\Api\CategoryPostRequest;
use Motor\Admin\Http\Resources\CategoryCollection;
use Motor\Admin\Http\Resources\CategoryResource;
use Motor\Admin\Models\Category;
use Motor\Admin\Services\CategoryService;
use Motor\Core\Filter\Renderers\WhereRenderer;

/**
 * Class CategoriesController
 */
class CategoriesController extends ApiController
{
    protected string $model = Category::class;

    protected string $modelResource = 'category';

    /**
     * List/search all records
     *
     * This will return a paginated response. Some limited search operations are also possible.
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<CategoryCollection>>
     */
    public function index(Category $categoryTree, CategoryGetRequest $request): CategoryCollection|JsonResponse
    {
        $service = CategoryService::collection();

        if (! is_null($categoryTree->parent_id)) {
            return response()->json(['message' => 'Category tree not found'], 404);
        }

        if (is_null($categoryTree->id)) {
            $scope = $request->get('scope');
        } else {
            $scope = $categoryTree->scope;
        }

        $filter = $service->getFilter();
        $filter->add(new WhereRenderer('scope'))
            ->setValue($scope);
        $filter->add(new WhereRenderer('parent_id'))
            ->setOperator('!=')
            ->setAllowNull(true)
            ->setValue(null);

        $service->setSorting([NestedSet::LFT, 'ASC']);
        $paginator = $service->getPaginator();

        return new CategoryCollection($paginator)->additional(['message' => 'Category collection read']);
    }

    /**
     * Create record
     */
    public function store(CategoryPostRequest $request): JsonResponse
    {
        $result = CategoryService::create($request)
            ->getResult();

        return new CategoryResource($result)->additional(['message' => 'Category created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(Category $categoryTree, Category $category): CategoryResource
    {
        $result = CategoryService::show($category)
            ->getResult();

        return new CategoryResource($result)->additional(['message' => 'Category read']);
    }

    /**
     * Update record
     */
    public function update(CategoryPatchRequest $request, Category $categoryTree, Category $category): CategoryResource
    {
        $result = CategoryService::update($category, $request)
            ->getResult();

        return new CategoryResource($result)->additional(['message' => 'Category updated']);
    }

    /**
     * Delete record
     */
    public function destroy(Category $category): JsonResponse
    {
        $result = CategoryService::delete($category)
            ->getResult();

        if ($result) {
            return response()->json(['message' => 'Category deleted']);
        }

        return response()->json(['message' => 'Problem deleting category'], 400);
    }
}
