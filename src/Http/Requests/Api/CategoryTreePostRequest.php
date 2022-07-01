<?php

namespace Motor\Admin\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Motor\Admin\Http\Requests\Request;

/**
 * Class CategoryTreePostRequest
 */
class CategoryTreePostRequest extends Request
{
    /**
     * @OA\Schema(
     *   schema="CategoryTreePostRequest",
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     example="New category tree"
     *   ),
     *   @OA\Property(
     *     property="scope",
     *     type="string",
     *     example="new-category-scope"
     *   ),
     *   required={"name", "scope"},
     * )
     */

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $request = $this;

        return [
            'name'  => 'required',
            'scope' => [
                'required',
                Rule::unique('categories')
                    ->where(function ($query) use ($request) {
                        return $query->where('scope', $request->scope)
                                     ->where('parent_id', null);
                    }),
            ],
        ];
    }
}
