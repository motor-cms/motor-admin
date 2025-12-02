<?php

namespace Motor\Admin\Http\Resources\V2;

use Motor\Core\Http\Resources\V2\BaseCollection;

class PermissionCollection extends BaseCollection
{
    public $collects = PermissionResource::class;
}
