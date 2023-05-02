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

    public function beforeCreate()
    {
        if (empty($this->slug)) {
            $this->createSlug();
        }
    }

    public function beforeUpdate()
    {
        if (empty($this->slug)) {
            $this->createSlug();
        }
    }

    public function createSlug()
    {
        $this->slug = Str::kebab($this->name);
    }
}
