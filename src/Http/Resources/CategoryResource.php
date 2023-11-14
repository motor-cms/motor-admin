<?php

namespace Motor\Admin\Http\Resources;

/**
 * @OA\Schema(
 *   schema="CategoryResource",
 *
 *   @OA\Property(
 *     property="id",
 *     type="integer",
 *     example="1"
 *   ),
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="My category"
 *   ),
 *   @OA\Property(
 *     property="scope",
 *     type="string",
 *     example="my-category-tree"
 *   ),
 *   @OA\Property(
 *     property="parent_id",
 *     type="integer",
 *     example="1"
 *   ),
 *   @OA\Property(
 *     property="_lft",
 *     type="integer",
 *     example="3"
 *   ),
 *   @OA\Property(
 *     property="_rgt",
 *     type="integer",
 *     example="5"
 *   ),
 *   @OA\Property(
 *     property="level",
 *     type="integer",
 *     example="1"
 *   ),
 *   @OA\Property(
 *     property="children",
 *     type="array",
 *
 *     @OA\Items(
 *       ref="#/components/schemas/CategoryResource"
 *     )
 *   )
 * )
 */
class CategoryResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        //if ($request->route()->compiled->getStaticPrefix() === '/api/category_trees') {
        $this->load('children');
        //}

        return [
            'id'        => (int) $this->id,
            'name'      => $this->name,
            'scope'     => $this->scope,
            'parent_id' => (int) $this->parent_id,
            '_lft'      => (int) $this->_lft,
            '_rgt'      => (int) $this->_rgt,
            'level'     => (int) $this->ancestors()
                ->count(),
            'children'  => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
