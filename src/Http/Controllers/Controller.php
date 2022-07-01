<?php

namespace Motor\Admin\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Motor\Admin\Models\User;

/**
 * Class Controller
 */
class Controller extends BaseController
{
    protected string $userModel = User::class;

    protected string $modelResource = '';

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        if ($this->userModel && $this->modelResource) {
            $this->authorizeResource($this->userModel, $this->modelResource);
        }
        \Locale::setDefault(config('app.locale'));
    }
}
