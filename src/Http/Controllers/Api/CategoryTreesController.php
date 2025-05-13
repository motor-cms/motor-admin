<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Kalnoy\Nestedset\NestedSet;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\CategoryTreeGetRequest;
use Motor\Admin\Http\Requests\Api\CategoryTreePatchRequest;
use Motor\Admin\Http\Requests\Api\CategoryTreePostRequest;
use Motor\Admin\Http\Resources\CategoryCollection;
use Motor\Admin\Http\Resources\CategoryTreeResource;
use Motor\Admin\Models\Category;
use Motor\Admin\Services\CategoryService;
use Motor\Core\Filter\Renderers\WhereRenderer;

/**
 * Class CategoriesController
 */
class CategoryTreesController extends ApiController
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
    public function index(CategoryTreeGetRequest $request): CategoryCollection
    {
        $service = CategoryService::collection();

        $filter = $service->getFilter();
        $filter->add(new WhereRenderer('parent_id'))
            ->setDefaultValue(null)
            ->setAllowNull(true);

        $service->setSorting([NestedSet::LFT, 'ASC']);
        $paginator = $service->getPaginator();

        return new CategoryCollection($paginator)->additional(['message' => 'Category tree collection read']);
    }

    /**
     * Create record
     */
    public function store(CategoryTreePostRequest $request): JsonResponse
    {
        $result = CategoryService::create($request)
            ->getResult();

        return new CategoryTreeResource($result)->additional(['message' => 'Category tree created'])
                                                ->response()
                                                ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(Category $categoryTree): CategoryTreeResource
    {
        $result = CategoryService::show($categoryTree)
            ->getResult();

        return new CategoryTreeResource($result->load('children'))->additional(['message' => 'Category tree read']);
    }

    /**
     * Get a single record by scope
     */
    public function byScope(string $scope): CategoryTreeResource
    {
        $categoryTree = Category::where('scope', $scope)->firstOrFail();
        $result = CategoryService::show($categoryTree)
            ->getResult();

        return new CategoryTreeResource($result->load('children'))->additional(['message' => 'Category tree read']);
    }

    /**
     * Update record
     */
    public function update(CategoryTreePatchRequest $request, Category $categoryTree): CategoryTreeResource
    {
        $result = CategoryService::update($categoryTree, $request)
            ->getResult();

        return (new CategoryTreeResource($result))->additional(['message' => 'Category tree updated']);
    }

    /**
     * Delete record
     */
    public function destroy(Category $categoryTree): JsonResponse
    {
        $result = CategoryService::delete($categoryTree)
            ->getResult();

        if ($result) {
            return response()->json(['message' => 'Category tree deleted']);
        }

        return response()->json(['message' => 'Problem deleting category tree'], 400);
    }
}
