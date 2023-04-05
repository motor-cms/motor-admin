<?php

namespace Motor\Assistant\Services;

use Motor\Admin\Http\Requests\Request;
use Motor\Admin\Models\CustomerCenter;
use Motor\Admin\Services\BaseService;

/**
 * Class ClickpathService
 * @package Motor\Assistant\Services
 */
class CustomerCenterService extends BaseService
{
    protected $model = CustomerCenter::class;
}
