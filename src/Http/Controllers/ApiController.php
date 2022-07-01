<?php

namespace Motor\Admin\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Class ApiController
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
