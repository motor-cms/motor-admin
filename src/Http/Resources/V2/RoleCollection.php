<?php

namespace Motor\Admin\Http\Resources\V2;

use Motor\Core\Http\Resources\V2\BaseCollection;

class RoleCollection extends BaseCollection
{
    public $collects = RoleResource::class;
}
