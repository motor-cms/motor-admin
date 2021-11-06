<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class ConfigVariableRequest
 *
 * @package Motor\Admin\Http\Requests\Admin
 */
class ConfigVariableRequest extends Request
{
    /**
     * @OA\Schema(
     *   schema="ConfigVariableRequest",
     *   @OA\Property(
     *     property="package",
     *     type="string",
     *     example="motor-admin"
     *   ),
     *   @OA\Property(
     *     property="group",
     *     type="string",
     *     example="project"
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     example="name"
     *   ),
     *   @OA\Property(
     *     property="value",
     *     type="string",
     *     example="My awesome project"
     *   ),
     *   required={"package", "group", "name", "value"},
     * )
     */

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'package' => 'required',
            'group'   => 'required',
            'name'    => 'required',
            'value'   => 'required',
        ];
    }
}
