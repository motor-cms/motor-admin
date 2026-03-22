<?php

namespace Motor\Admin\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Models\Language;
use Motor\Core\Filter\Renderers\SelectRenderer;

/**
 * Class EmailTemplateService
 */
class EmailTemplateService extends BaseService
{
    protected string $sortableField = 'updated_at';

    protected string $sortableDirection = 'DESC';

    protected array $loadColumns = ['client', 'language'];

    protected string $model = EmailTemplate::class;

    public function filters(): void
    {
        $this->filter->addClientFilter();
        $this->filter->add(new SelectRenderer('language_id'))
            ->setOptions(Language::pluck('english_name', 'id'));
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
