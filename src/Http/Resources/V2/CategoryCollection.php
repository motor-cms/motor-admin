<?php

namespace Motor\Admin\Http\Resources\V2;

use Motor\Core\Http\Resources\V2\BaseCollection;

class CategoryCollection extends BaseCollection
{
    public $collects = CategoryResource::class;
}
