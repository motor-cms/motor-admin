<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Kalnoy\Nestedset\NestedSet;
use Motor\Admin\Http\Controllers\ApiController;
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
     * @OA\Get (
     *   tags={"CategoryTreesController"},
     *   path="/api/category_trees",
     *   summary="Get category tree collection",
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
     *         @OA\Items(ref="#/components/schemas/CategoryTreeResource")
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
    public function index(): CategoryCollection
    {
        $service = CategoryService::collection();

        $filter = $service->getFilter();
        $filter->add(new WhereRenderer('parent_id'))
            ->setDefaultValue(null)
            ->setAllowNull(true);

        $service->setSorting([NestedSet::LFT, 'ASC']);
        $paginator = $service->getPaginator();

        return (new CategoryCollection($paginator))->additional(['message' => 'Category tree collection read']);
    }

    /**
     * @OA\Post (
     *   tags={"CategoryTreesController"},
     *   path="/api/category_trees",
     *   summary="Create new category tree",
     *
     *   @OA\RequestBody(
     *
     *     @OA\JsonContent(ref="#/components/schemas/CategoryTreePostRequest")
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
     *         ref="#/components/schemas/CategoryTreeResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Category tree created"
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
    public function store(CategoryTreePostRequest $request): JsonResponse
    {
        $result = CategoryService::create($request)
            ->getResult();

        return (new CategoryTreeResource($result))->additional(['message' => 'Category tree created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get (
     *   tags={"CategoryTreesController"},
     *   path="/api/category_trees/{category}",
     *   summary="Get single category tree",
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
     *     name="category",
     *     parameter="category",
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
     *         ref="#/components/schemas/CategoryTreeResource"
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
    public function show(Category $categoryTree): CategoryTreeResource
    {
        $result = CategoryService::show($categoryTree)
            ->getResult();

        return (new CategoryTreeResource($result->load('children')))->additional(['message' => 'Category tree read']);
    }

    /**
     * @OA\Get (
     *   tags={"CategoryTreesController"},
     *   path="/api/category_trees/scope/{scope}",
     *   summary="Get single category tree by scope",
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
     *     name="scope",
     *     parameter="scope",
     *     description="Category tree scope"
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
     *         ref="#/components/schemas/CategoryTreeResource"
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
    public function byScope(string $scope): CategoryTreeResource
    {
        $categoryTree = Category::where('scope', $scope)->firstOrFail();
        $result = CategoryService::show($categoryTree)
            ->getResult();

        return (new CategoryTreeResource($result->load('children')))->additional(['message' => 'Category tree read']);
    }

    /**
     * @OA\Put (
     *   tags={"CategoryTreesController"},
     *   path="/api/category_trees/{category}",
     *   summary="Update an existing category tree",
     *
     *   @OA\RequestBody(
     *
     *     @OA\JsonContent(ref="#/components/schemas/CategoryTreePatchRequest")
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
     *     name="category",
     *     parameter="category",
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
     *         ref="#/components/schemas/CategoryTreeResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Category tree updated"
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
    public function update(CategoryTreePatchRequest $request, Category $categoryTree): CategoryTreeResource
    {
        $result = CategoryService::update($categoryTree, $request)
            ->getResult();

        return (new CategoryTreeResource($result))->additional(['message' => 'Category tree updated']);
    }

    /**
     * @OA\Delete (
     *   tags={"CategoryTreesController"},
     *   path="/api/category_trees/{category}",
     *   summary="Delete a category tree",
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
     *     name="category",
     *     parameter="category",
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
     *         property="message",
     *         type="string",
     *         example="Category tree deleted"
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
     *         example="Problem deleting category tree"
     *       )
     *     )
     *   )
     * )
     *
     * Remove the specified resource from storage.
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
