<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class CategoryRequest
 *
 * @OA\Schema(
 *   schema="CategoryRequest",
 *
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="New category name"
 *   ),
 *   @OA\Property(
 *     property="parent_id",
 *     type="integer",
 *     example="1"
 *   ),
 *   @OA\Property(
 *     property="previous_sibling_id",
 *     type="integer",
 *     example="2"
 *   ),
 *   @OA\Property(
 *     property="next_sibling_id",
 *     type="integer",
 *     example="4"
 *   ),
 *   required={"name", "parent_id"},
 * )
 */
class CategoryRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'                => 'required',
            'parent_id'           => 'required',
            'previous_sibling_id' => 'nullable',
            'next_sibling_id'     => 'nullable',
        ];
    }
}
