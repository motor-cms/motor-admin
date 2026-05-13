<?php

namespace Motor\Admin\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Motor\Admin\Models\User;
use Motor\Core\Filter\Renderers\RelationRenderer;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

/**
 * Class UserService
 */
class UserService extends BaseService
{
    protected array $loadColumns = ['clients', 'roles'];

    protected string $model = User::class;

    public function filters(): void
    {
        // Users have a many-to-many relationship with clients via the
        // users_client pivot table, so the generic addClientFilter()
        // (which adds WHERE users.client_id = ?) cannot be used here.
        // Use RelationRenderer to join through the pivot table instead.
        //
        // Phase 7 of ZRMDEV-165: read the first pivot client instead of the
        // dropped users.client_id scalar. V1-strict semantics -- a multi-client
        // user collapses to their first pivot row.
        $client = Auth::user()?->clients->first();

        if ($client !== null) {
            $this->filter->add(new RelationRenderer('client_id', 'users_client.user_id'))
                ->setJoin('users_client')
                ->setOptions([$client->id => $client->name])
                ->setDefaultValue($client->id)
                ->isVisible(false);
        } else {
            $clients = config('motor-admin.models.client')::orderBy('name')->pluck('name', 'id');
            $this->filter->add(new RelationRenderer('client_id', 'users_client.user_id'))
                ->setJoin('users_client')
                ->setOptions($clients);
        }
    }

    public function beforeCreate(): void
    {
        // Phase 7 of ZRMDEV-165: pivot migration. Pre-fill the new user's
        // clients with the creator's first pivot client when one exists, so
        // the new account inherits the creating tenant.
        //
        // Phase 9 follow-up: write into $this->data['clients'] (which
        // syncClients() reads on afterCreate) instead of the dead-code
        // assignment to $this->record->clients (a property name that shadows
        // the BelongsToMany relation -- it never persisted, AND it broke the
        // relation cache for any in-request reader). Use ??= so an explicit
        // request payload still wins.
        $client = Auth::user()?->clients->first();
        if ($client !== null) {
            $this->data['clients'] ??= [$client->id];
        }
        $this->data['api_token'] = Str::random(60);
        $this->updatePassword();
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function afterCreate(): void
    {
        $this->syncClients();
        $this->syncRolesAndPermissions();
        $this->uploadFiles();
    }

    public function beforeUpdate(): void
    {
        // Special case to filter out the users api token when calling over the api
        if (Arr::get($this->data, 'api_token')) {
            unset($this->data['api_token']);
        }
        $this->updatePassword();
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function afterUpdate(): void
    {
        $this->syncClients();
        $this->syncRolesAndPermissions();
        $this->uploadFiles();
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
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    private function uploadFiles(): void
    {
        $this->uploadFile(Arr::get($this->data, 'avatar'), 'avatar');
    }

    private function syncClients(): void
    {
        if (Arr::has($this->data, 'clients')) {
            $this->record->clients()->sync(Arr::get($this->data, 'clients', []));
        }
    }

    private function syncRolesAndPermissions(): void
    {
        if (Arr::get($this->data, 'roles')) {
            $this->record->syncRoles(Arr::get($this->data, 'roles', []));
            $this->record->syncPermissions(Arr::get($this->data, 'permissions', []));
        }
    }
}
