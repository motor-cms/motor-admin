<?php

namespace Motor\Admin\Services;

use Motor\Admin\Models\Domain;
use Motor\Core\Filter\Renderers\WhereRenderer;

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
        $this->filter->add(new WhereRenderer('is_active'));
    }
}
