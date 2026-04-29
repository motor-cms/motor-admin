<?php

namespace Motor\Admin\Http\Resources\V2;

use Motor\Core\Http\Resources\V2\BaseCollection;

class ConfigVariableCollection extends BaseCollection
{
    public $collects = ConfigVariableResource::class;
}
