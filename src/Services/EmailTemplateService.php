<?php

namespace Motor\Admin\Services;

use Illuminate\Support\Arr;
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
        $this->createSlug();
    }

    public function beforeUpdate(): void
    {
        $this->createSlug();
    }

    protected function createSlug(): void
    {
        $slug = Arr::get($this->data, 'slug');
        if (is_null($slug)) {
            $this->data['slug'] = Str::kebab(Arr::get($this->data, 'name'));
        }
    }
}
