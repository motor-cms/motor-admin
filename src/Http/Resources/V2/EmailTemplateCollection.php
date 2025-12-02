<?php

namespace Motor\Admin\Http\Resources\V2;

use Motor\Core\Http\Resources\V2\BaseCollection;

class EmailTemplateCollection extends BaseCollection
{
    public $collects = EmailTemplateResource::class;
}
