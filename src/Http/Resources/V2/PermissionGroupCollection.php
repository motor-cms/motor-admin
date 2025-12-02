<?php

namespace Motor\Admin\Http\Resources\V2;

use Motor\Core\Http\Resources\V2\BaseCollection;

class PermissionGroupCollection extends BaseCollection
{
    public $collects = PermissionGroupResource::class;
}
