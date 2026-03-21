<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Kra8\Snowflake\HasShortflakePrimary;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Scout\Searchable;
use Motor\Admin\Database\Factories\UserFactory;
use Motor\Core\Traits\Filterable;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

/**
 * Motor\Admin\Models\User
 *
 * @property int $id
 * @property int|null $client_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $api_token
 * @property string|null $remember_token
 * @property string|null $password_last_changed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Client|null $client
 * @property-read MediaCollection|Media[] $media
 * @property-read int|null $media_count
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection|Role[] $roles
 * @property-read int|null $roles_count
 * @property-read Collection|PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Motor\Admin\Database\Factories\UserFactory factory(...$parameters)
 * @method static Builder|User filteredBy(\Motor\Core\Filter\Filter $filter, $column)
 * @method static Builder|User filteredByMultiple(\Motor\Core\Filter\Filter $filter)
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User permission($permissions)
 * @method static Builder|User query()
 * @method static Builder|User role($roles, $guard = null)
 * @method static Builder|User search($query, $full_text = false)
 * @method static Builder|User whereApiToken($value)
 * @method static Builder|User whereClientId($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereName($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User wherePasswordLastChangedAt($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements HasMedia
{
    use Filterable;
    use HasApiTokens;
    use HasFactory;
    use HasPermissions;
    use HasRoles;
    use HasShortflakePrimary;
    use InteractsWithMedia;
    use Notifiable;
    use Searchable;

    protected string $guard_name = 'web';

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'motor_admin_users_index';
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id'    => (int) $this->id,
            'name'  => $this->name,
            'email' => $this->email,
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * @throws InvalidManipulation
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(400);
        $this->addMediaConversion('preview')
            ->width(400)
            ->height(400);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'client_id',
        'name',
        'email',
        'password',
        'api_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'users_client', 'user_id', 'client_id');
    }

    /**
     * Checks if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(['SuperAdmin']);
    }
}
