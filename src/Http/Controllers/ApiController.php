<?php

namespace Motor\Admin\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *   title="Motor Admin API",
 *   version="2.0.0",
 *   x={
 *     "logo": {
 *       "url": "https://motor-cms.com/motor.png",
 *       "altText": "Logo"
 *     }
 *   },
 *   @OA\Contact(
 *     email="me@dfox.info"
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="AccessDenied",
 *   type="json",
 *   example={"error": "Unauthenticated"},
 *   description="The user is not authorized to make this request"
 * )
 *
 * @OA\Schema(
 *   schema="NotFound",
 *   type="json",
 *   example={"message": "Record not found"},
 *   description="The record was not found in the database"
 * )
 *
 * @OA\Schema(
 *   schema="FileUpload",
 *   @OA\Property(
 *     property="dataUrl",
 *     type="string",
 *     example="ABCDEF",
 *     description="Must be a valid base64 encoded image (png or jpg)"
 *   ),
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="my-file.jpg",
 *     description="If empty, a temporary filename will be generated"
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="PaginationLinks",
 *   @OA\Property(
 *     property="first",
 *     type="string",
 *     example="http://localhost/api/endpoint?page=1"
 *   ),
 *   @OA\Property(
 *     property="last",
 *     type="string",
 *     example="http://localhost/api/endpoint?page=3"
 *   ),
 *   @OA\Property(
 *     property="prev",
 *     type="'null',string",
 *     example="null"
 *   ),
 *   @OA\Property(
 *     property="next",
 *     type="'null',string",
 *     example="http://localhost/api/endpoint?page=2"
 *   ),
 * )
 *
 * @OA\Schema(
 *   schema="PaginationMeta",
 *   @OA\Property(
 *     property="current_page",
 *     type="integer",
 *     example="1"
 *   ),
 *   @OA\Property(
 *     property="last_page",
 *     type="integer",
 *     example="2"
 *   ),
 *   @OA\Property(
 *     property="from",
 *     type="integer",
 *     example="1"
 *   ),
 *   @OA\Property(
 *     property="to",
 *     type="integer",
 *     example="25"
 *   ),
 *   @OA\Property(
 *     property="total",
 *     type="integer",
 *     example="28"
 *   ),
 *   @OA\Property(
 *     property="per_page",
 *     type="integer",
 *     example="25"
 *   ),
 *   @OA\Property(
 *     property="path",
 *     type="string",
 *     example="http://localhost/api/endpoint"
 *   ),
 *   @OA\Property(
 *     property="links",
 *     type="array",
 *     @OA\Items(
 *       @OA\Property(
 *         property="url",
 *         type="'null',string",
 *         example="http://localhost/api/endpoint?page=1"
 *       ),
 *       @OA\Property(
 *         property="label",
 *         type="string",
 *         example="Previous"
 *       ),
 *       @OA\Property(
 *         property="active",
 *         type="boolean",
 *         example="false"
 *       )
 *     )
 *   )
 * )
 */
class ApiController extends BaseController
{
    protected string $modelResource = '';

    protected string $model = '';

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        if ($this->model && $this->modelResource) {
            $this->authorizeResource($this->model, $this->modelResource);
        }
        \Locale::setDefault(config('app.locale'));
    }
}
