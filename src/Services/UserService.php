<?php

namespace Motor\Admin\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Motor\Admin\Models\User;

/**
 * Class UserService
 */
class UserService extends BaseService
{
    protected $model = User::class;

    public function filters(): void
    {
        $this->filter->addClientFilter();
    }

    public function beforeCreate(): void
    {
        if (Auth::user()->client_id > 0) {
            $this->record->client_id = Auth::user()->client_id;
        }
        $this->data['api_token'] = Str::random(60);
        $this->updateClientId();
        $this->updatePassword();
    }

    /**
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    public function afterCreate(): void
    {
        $this->syncRolesAndPermissions();
        $this->uploadFiles();
    }

    public function beforeUpdate(): void
    {
        // Special case to filter out the users api token when calling over the api
        if (Arr::get($this->data, 'api_token')) {
            unset($this->data['api_token']);
        }
        $this->updateClientId();
        $this->updatePassword();
    }

    /**
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    public function afterUpdate(): void
    {
        $this->syncRolesAndPermissions();
        $this->uploadFiles();
    }

    private function updateClientId(): void
    {
        if (! Arr::get($this->data, 'client_id')) {
            $this->data['client_id'] = null;
        }
    }

    private function updatePassword(): void
    {
        if (Arr::get($this->data, 'password') == '') {
            unset($this->data['password']);
        } else {
            $this->data['password'] = bcrypt($this->data['password']);
        }
    }

    /**
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    private function uploadFiles(): void
    {
        $this->uploadFile(Arr::get($this->data, 'avatar'), 'avatar');
    }

    private function syncRolesAndPermissions(): void
    {
        if (Arr::get($this->data, 'roles')) {
            $this->record->syncRoles(Arr::get($this->data, 'roles', []));
        }
    }
}
