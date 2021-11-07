<?php

namespace Motor\Admin\Http\Resources;

/**
 * @OA\Schema(
 *   schema="CategoryTreeResource",
 *   @OA\Property(
 *     property="id",
 *     type="integer",
 *     example="1"
 *   ),
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="My category tree"
 *   ),
 *   @OA\Property(
 *     property="scope",
 *     type="string",
 *     example="my-category-tree"
 *   ),
 *   @OA\Property(
 *     property="children",
 *     type="array",
 *     @OA\Items(
 *       ref="#/components/schemas/CategoryResource"
 *     )
 *   )
 * )
 */
class CategoryTreeResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id'       => (int) $this->id,
            'name'     => $this->name,
            'scope'    => $this->scope,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
