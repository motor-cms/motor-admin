<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kalnoy\Nestedset\NestedSet;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\CategoryRequest;
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
     * @OA\Get (
     *   tags={"CategoriesController"},
     *   path="/api/category_trees/{category_tree}/categories",
     *   summary="Get categories collection",
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
     *     name="category_tree",
     *     parameter="category_tree",
     *     description="Category tree id"
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
     *         @OA\Items(ref="#/components/schemas/CategoryResource")
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
     *   ),
     *
     *   @OA\Response(
     *     response="404",
     *     description="Category tree not found",
     *
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Display a listing of the resource.
     */
    public function index(Category $categoryTree, Request $request): CategoryCollection|JsonResponse
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

        return (new CategoryCollection($paginator))->additional(['message' => 'Category collection read']);
    }

    /**
     * @OA\Post (
     *   tags={"CategoriesController"},
     *   path="/api/category_trees/{category_tree}/categories",
     *   summary="Create new category",
     *
     *   @OA\RequestBody(
     *
     *     @OA\JsonContent(ref="#/components/schemas/CategoryRequest")
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
     *     name="category_tree",
     *     parameter="category_tree",
     *     description="Category tree id"
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
     *         ref="#/components/schemas/CategoryResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Category created"
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
    public function store(CategoryRequest $request): JsonResponse
    {
        $result = CategoryService::create($request)
            ->getResult();

        return (new CategoryResource($result))->additional(['message' => 'Category created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get (
     *   tags={"CategoriesController"},
     *   path="/api/category_trees/{category_tree}/categories/{category}",
     *   summary="Get single category",
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
     *     name="category_tree",
     *     parameter="category_tree",
     *     description="Category tree id"
     *   ),
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="category",
     *     parameter="category",
     *     description="Category id"
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
     *         ref="#/components/schemas/CategoryResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Category tree read"
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
    public function show($parent, Category $record): CategoryResource // Typecase on parent fails with a type error
    {
        $result = CategoryService::show($record)
            ->getResult();

        return (new CategoryResource($result))->additional(['message' => 'Category read']);
    }

    /**
     * @OA\Put (
     *   tags={"CategoriesController"},
     *   path="/api/category_trees/{category_tree}/categories/{category}",
     *   summary="Update an existing category",
     *
     *   @OA\RequestBody(
     *
     *     @OA\JsonContent(ref="#/components/schemas/CategoryRequest")
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
     *     name="category_tree",
     *     parameter="category_tree",
     *     description="Category tree id"
     *   ),
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="category",
     *     parameter="category",
     *     description="Category id"
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
     *         ref="#/components/schemas/CategoryResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Category updated"
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
    public function update(CategoryRequest $request, $parent, Category $record): CategoryResource
    {
        $result = CategoryService::update($record, $request)
            ->getResult();

        return (new CategoryResource($result))->additional(['message' => 'Category updated']);
    }

    /**
     * @OA\Delete (
     *   tags={"CategoriesController"},
     *   path="/api/category_trees/{category_tree}/categories/{category}",
     *   summary="Delete a category",
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
     *     name="category_tree",
     *     parameter="category_tree",
     *     description="Category tree id"
     *   ),
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="category",
     *     parameter="category",
     *     description="Category id"
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
     *         example="Category deleted"
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
     *         example="Problem deleting category"
     *       )
     *     )
     *   )
     * )
     *
     * Remove the specified resource from storage.
     */
    public function destroy(Category $record): JsonResponse
    {
        $result = CategoryService::delete($record)
            ->getResult();

        if ($result) {
            return response()->json(['message' => 'Category deleted']);
        }

        return response()->json(['message' => 'Problem deleting category'], 400);
    }
}
