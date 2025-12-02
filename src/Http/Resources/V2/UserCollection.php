<?php

namespace Motor\Admin\Http\Resources\V2;

use Motor\Core\Http\Resources\V2\BaseCollection;

/**
 * V2 UserCollection with standardized pagination.
 */
class UserCollection extends BaseCollection
{
    public $collects = UserResource::class;
}
