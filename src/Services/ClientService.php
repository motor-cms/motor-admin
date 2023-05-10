<?php

namespace Motor\Admin\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Motor\Admin\Models\Client;

/**
 * Class ClientService
 */
class ClientService extends BaseService
{
    protected $model = Client::class;

    public function beforeCreate(): void
    {
        $this->createSlug();
    }

    public function beforeUpdate(): void
    {
        $this->createSlug();
    }

    private function createSlug(): void
    {
        if (Arr::get($this->data, 'slug') == '') {
            $this->data['slug'] = Str::slug($this->data['name']);
        } else {
            $this->data['slug'] = Str::slug($this->data['slug']);
        }
    }
}
