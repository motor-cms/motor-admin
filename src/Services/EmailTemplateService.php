<?php

namespace Motor\Admin\Services;

use Illuminate\Support\Str;
use Motor\Admin\Models\EmailTemplate;

/**
 * Class EmailTemplateService
 */
class EmailTemplateService extends BaseService
{
    protected $model = EmailTemplate::class;

    public function filters(): void
    {
        $this->filter->addClientFilter();
    }

    public function beforeCreate(): void
    {
        if (empty($this->slug)) {
            $this->createSlug();
        }
    }

    public function beforeUpdate(): void
    {
        if (empty($this->slug)) {
            $this->createSlug();
        }
    }

    protected function createSlug(): void
    {
        $this->slug = Str::kebab($this->name);
    }
}
