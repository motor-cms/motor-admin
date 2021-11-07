<?php

namespace Motor\Admin\Http\Resources;

/**
 * @OA\Schema(
 *   schema="RoleResource",
 *   @OA\Property(
 *     property="id",
 *     type="integer",
 *     example="1"
 *   ),
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="Administrator"
 *   ),
 *   @OA\Property(
 *     property="guard_name",
 *     type="string",
 *     example="web"
 *   ),
 *   @OA\Property(
 *     property="permissions",
 *     type="array",
 *     @OA\Items(
 *       ref="#/components/schemas/PermissionResource"
 *     ),
 *   )
 * )
 */
class RoleResource extends BaseResource
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
            "id"          => $this->id,
            "name"        => $this->name,
            "guard_name"  => $this->guard_name,
            'permissions' => PermissionResource::collection($this->permissions),
        ];
    }
}
