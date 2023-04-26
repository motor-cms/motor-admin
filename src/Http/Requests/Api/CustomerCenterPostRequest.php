<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class CustomerCenterPostRequest
 *
 * @OA\Schema(
 *   schema="CustomerCenterPostRequest",
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="My beautiful name"
 *   ),
 *   @OA\Property(
 *     property="description",
 *     type="string",
 *     example="My beautiful description"
 *   ),
 *   @OA\Property(
 *     property="address",
 *     type="string",
 *     example="My beautiful address"
 *   ),
 *   @OA\Property(
 *     property="zip",
 *     type="string",
 *     example="My beautiful zip"
 *   ),
 *   @OA\Property(
 *     property="city",
 *     type="string",
 *     example="My beautiful city"
 *   ),
 *   @OA\Property(
 *     property="country_iso_3166_1",
 *     type="string",
 *     example="My beautiful country_iso_3166_1"
 *   ),
 *   @OA\Property(
 *     property="gmaps_url",
 *     type="string",
 *     example="My beautiful ugmaps_url"
 *   ),
 *   @OA\Property(
 *     property="phone",
 *     type="string",
 *     example="My beautiful phone"
 *   ),
 *   @OA\Property(
 *     property="fax",
 *     type="string",
 *     example="My beautiful fax"
 *   ),
 *   @OA\Property(
 *     property="email",
 *     type="string",
 *     example="user@domain.com"
 *   ),
 *   @OA\Property(
 *     property="latitude",
 *     type="string",
 *     example="My beautiful latitude"
 *   ),
 *   @OA\Property(
 *     property="longitude",
 *     type="string",
 *     example="My beautiful longitude"
 *   ),
 *   @OA\Property(
 *     property="opening_times",
 *     type="string",
 *     example="My beautiful opening_times"
 *   ),
 *   required={"name", "address", "zip", "city", "country_iso_3166_1"}
 * )
 */
class CustomerCenterPostRequest extends Request
{

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
            'name'           => 'required',
            'description'       => 'required',
            'address'       => 'required',
            'zip'       => 'required',
            'city'       => 'required',
            'country_iso_3166_1'       => 'required',
            'gmaps_url'          => 'nullable|string',
            'phone'          => 'nullable|string',
            'fax'          => 'nullable|string',
            'email'          => 'nullable|string',
            'latitude'          => 'nullable|double',
            'longitude'          => 'nullable|double',
            'opening_times'          => 'nullable|json',
        ];
    }
}
