<?php

namespace Motor\Admin\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Motor\Admin\Models\Client;

/**
 * Class ClientService
 *
 * @package Motor\Admin\Services
 */
class ClientService extends BaseService
{
    protected $model = Client::class;

    function beforeCreate()
    {
        $this->createSlug();
    }

    function beforeUpdate()
    {
        $this->createSlug();
    }

    private function createSlug()
    {
        if (Arr::get($this->data, 'slug') == '') {
            $this->data['slug'] = Str::slug($this->data['name']);
        } else {
            $this->data['slug'] = Str::slug($this->data['slug']);
        }
    }
}
