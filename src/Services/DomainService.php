<?php

namespace Motor\Admin\Services;

use Motor\Admin\Models\Domain;

/**
 * Class DomainService
 */
class DomainService extends BaseService
{
    protected array $loadColumns = ['client'];

    protected $model = Domain::class;

    public function filters(): void
    {
        $this->filter->addClientFilter();
    }
}
