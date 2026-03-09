<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Kalnoy\Nestedset\NestedSet;
use Motor\Admin\Http\Requests\Api\V2\CategoryTreeGetRequest;
use Motor\Admin\Http\Requests\Api\V2\CategoryTreePatchRequest;
use Motor\Admin\Http\Requests\Api\V2\CategoryTreePostRequest;
use Motor\Admin\Http\Resources\V2\CategoryCollection;
use Motor\Admin\Http\Resources\V2\CategoryTreeResource;
use Motor\Admin\Models\Category;
use Motor\Admin\Services\CategoryService;
use Motor\Core\Filter\Renderers\WhereRenderer;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * V2 CategoryTrees Controller.
 *
 * Children are loaded explicitly via eager loading, fixing Scramble's
 * recursive type inference issue while preserving full tree capability.
 *
 * @tags Category Trees
 */
class CategoryTreesController extends ApiController
{
    protected string $model = Category::class;

    protected string $modelResource = 'category';

    /**
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<CategoryResource>>
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

        return (new CategoryCollection($paginator))
            ->additional(['meta' => ['message' => 'Category trees retrieved']]);
    }

    public function show(Category $categoryTree): CategoryTreeResource
    {
        $result = CategoryService::show($categoryTree)
            ->getResult();

        // Explicitly load children tree (up to 5 levels deep)
        $result->load([
            'children' => fn ($q) => $q->orderBy(NestedSet::LFT),
            'children.children' => fn ($q) => $q->orderBy(NestedSet::LFT),
            'children.children.children' => fn ($q) => $q->orderBy(NestedSet::LFT),
            'children.children.children.children' => fn ($q) => $q->orderBy(NestedSet::LFT),
            'children.children.children.children.children' => fn ($q) => $q->orderBy(NestedSet::LFT),
        ]);

        return (new CategoryTreeResource($result))
            ->additional(['meta' => ['message' => 'Category tree retrieved']]);
    }

    public function byScope(string $scope): CategoryTreeResource
    {
        $categoryTree = Category::where('scope', $scope)->firstOrFail();

        $result = CategoryService::show($categoryTree)
            ->getResult();

        // Explicitly load children tree (up to 5 levels deep)
        $result->load([
            'children' => fn ($q) => $q->orderBy(NestedSet::LFT),
            'children.children' => fn ($q) => $q->orderBy(NestedSet::LFT),
            'children.children.children' => fn ($q) => $q->orderBy(NestedSet::LFT),
            'children.children.children.children' => fn ($q) => $q->orderBy(NestedSet::LFT),
            'children.children.children.children.children' => fn ($q) => $q->orderBy(NestedSet::LFT),
        ]);

        return (new CategoryTreeResource($result))
            ->additional(['meta' => ['message' => 'Category tree retrieved']]);
    }

    public function store(CategoryTreePostRequest $request): JsonResponse
    {
        $result = CategoryService::create($request)
            ->getResult();

        return (new CategoryTreeResource($result))
            ->additional(['meta' => ['message' => 'Category tree created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(CategoryTreePatchRequest $request, Category $categoryTree): CategoryTreeResource
    {
        $result = CategoryService::update($categoryTree, $request)
            ->getResult();

        return (new CategoryTreeResource($result))
            ->additional(['meta' => ['message' => 'Category tree updated']]);
    }

    public function destroy(Category $categoryTree): Response
    {
        CategoryService::delete($categoryTree);

        return $this->noContentResponse();
    }
}
