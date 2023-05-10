<?php

namespace Motor\Admin\Services;

use Illuminate\Support\Arr;
use Motor\Admin\Models\User;

/**
 * Class ProfileEditService
 */
class ProfileEditService extends BaseService
{
    protected $model = User::class;

    public function beforeUpdate(): void
    {
        if (Arr::get($this->data, 'password') == '') {
            unset($this->data['password']);
        } else {
            $this->data['password'] = bcrypt($this->data['password']);
        }
    }

    public function afterUpdate(): void
    {
        $this->uploadFile($this->request->file('avatar'), 'avatar');
    }
}
