<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Kalnoy\Nestedset\NestedSet;
use Motor\Admin\Http\Requests\Api\CategoryGetRequest;
use Motor\Admin\Http\Requests\Api\CategoryPatchRequest;
use Motor\Admin\Http\Requests\Api\CategoryPostRequest;
use Motor\Admin\Http\Resources\V2\CategoryCollection;
use Motor\Admin\Http\Resources\V2\CategoryResource;
use Motor\Admin\Models\Category;
use Motor\Admin\Services\CategoryService;
use Motor\Core\Filter\Renderers\WhereRenderer;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * V2 Categories Controller.
 *
 * @tags Categories
 */
class CategoriesController extends ApiController
{
    protected string $model = Category::class;

    protected string $modelResource = 'category';

    /**
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<CategoryResource>>
     */
    public function index(Category $categoryTree, CategoryGetRequest $request): CategoryCollection|JsonResponse
    {
        $service = CategoryService::collection();

        if (! is_null($categoryTree->parent_id)) {
            return $this->notFoundResponse('Category tree not found');
        }

        $scope = is_null($categoryTree->id)
            ? $request->get('scope')
            : $categoryTree->scope;

        $filter = $service->getFilter();
        $filter->add(new WhereRenderer('scope'))
            ->setValue($scope);
        $filter->add(new WhereRenderer('parent_id'))
            ->setOperator('!=')
            ->setAllowNull(true)
            ->setValue(null);

        $service->setSorting([NestedSet::LFT, 'ASC']);
        $paginator = $service->getPaginator();

        return (new CategoryCollection($paginator))
            ->additional(['meta' => ['message' => 'Categories retrieved']]);
    }

    public function show(Category $categoryTree, Category $category): CategoryResource
    {
        $result = CategoryService::show($category)
            ->getResult();

        // Load children if requested via query param
        if (request()->boolean('include_children')) {
            $result->load([
                'children' => fn ($q) => $q->orderBy(NestedSet::LFT),
                'children.children' => fn ($q) => $q->orderBy(NestedSet::LFT),
                'children.children.children' => fn ($q) => $q->orderBy(NestedSet::LFT),
            ]);
        }

        return (new CategoryResource($result))
            ->additional(['meta' => ['message' => 'Category retrieved']]);
    }

    public function store(CategoryPostRequest $request): JsonResponse
    {
        $result = CategoryService::create($request)
            ->getResult();

        return (new CategoryResource($result))
            ->additional(['meta' => ['message' => 'Category created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(CategoryPatchRequest $request, Category $categoryTree, Category $category): CategoryResource
    {
        $result = CategoryService::update($category, $request)
            ->getResult();

        return (new CategoryResource($result))
            ->additional(['meta' => ['message' => 'Category updated']]);
    }

    public function destroy(Category $category): Response
    {
        CategoryService::delete($category);

        return $this->noContentResponse();
    }
}
