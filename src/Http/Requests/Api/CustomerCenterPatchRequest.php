<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Api\CustomerCenterPostRequest;

/**
 * Class CustomerCenterPatchRequest
 *
 * @package Motor\Builder\Http\Requests\Api
 *
 * @OA\Schema(
 *   schema="CustomerCenterPatchRequest",
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
class CustomerCenterPatchRequest extends CustomerCenterPostRequest
{
    public function rules(): array
    {
        $parentRules = parent::rules();
        return $parentRules;
    }
}
