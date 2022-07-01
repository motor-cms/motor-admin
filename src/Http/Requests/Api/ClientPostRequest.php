<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class ClientPostRequest
 */
class ClientPostRequest extends Request
{
    /**
     * @OA\Schema(
     *   schema="ClientPostRequest",
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     example="New client"
     *   ),
     *   @OA\Property(
     *     property="slug",
     *     type="string",
     *     example="new-client"
     *   ),
     *   @OA\Property(
     *     property="address",
     *     type="string",
     *     example="1234 Motor drive"
     *   ),
     *   @OA\Property(
     *     property="zip",
     *     type="string",
     *     example="90210"
     *   ),
     *   @OA\Property(
     *     property="city",
     *     type="string",
     *     example="Hollywood"
     *   ),
     *   @OA\Property(
     *     property="country_iso_3166_1",
     *     type="string",
     *     example="US"
     *   ),
     *   @OA\Property(
     *     property="website",
     *     type="string",
     *     example="https://www.motor-cms.com"
     *   ),
     *   @OA\Property(
     *     property="description",
     *     type="string",
     *     example="A lengthy description of the client"
     *   ),
     *   @OA\Property(
     *     property="is_active",
     *     type="boolean",
     *     example="true"
     *   ),
     *   @OA\Property(
     *     property="contact_name",
     *     type="string",
     *     example="John Doe"
     *   ),
     *   @OA\Property(
     *     property="contact_email",
     *     type="string",
     *     example="john@doe.com"
     *   ),
     *   @OA\Property(
     *     property="contact_phone",
     *     type="string",
     *     example="+1 123 123 123"
     *   ),
     *   required={"name", "slug"},
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
        return [
            'name'               => 'required',
            'slug'               => 'required',
            'address'            => 'nullable',
            'zip'                => 'nullable',
            'city'               => 'nullable',
            'country_iso_3166_1' => 'nullable|min:2|max:2',
            'website'            => 'nullable|url',
            'description'        => 'nullable',
            'is_active'          => 'nullable|boolean',
            'contact_name'       => 'nullable',
            'contact_email'      => 'nullable|email',
            'contact_phone'      => 'nullable',
        ];
    }
}
