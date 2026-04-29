<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kra8\Snowflake\HasShortflakePrimary;
use Laravel\Scout\Searchable;
use Mattiverse\Userstamps\Traits\Userstamps;
use Motor\Admin\Database\Factories\ClientFactory;
use Motor\Core\Traits\Filterable;

/**
 * Motor\Admin\Models\Client
 *
 * @property int $id
 * @property string $slug
 * @property bool $is_active
 * @property string $name
 * @property string $address
 * @property string $zip
 * @property string $city
 * @property string $country_iso_3166_1
 * @property string $website
 * @property string $contact_name
 * @property string $contact_phone
 * @property string $contact_email
 * @property string $description
 * @property array|null $frontend_config
 * @property int $created_by
 * @property int $updated_by
 * @property int|null $deleted_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Domain[] $domains
 * @property-read int|null $domains_count
 * @property-read Collection|User[] $users
 * @property-read int|null $users_count
 *
 * @method static \Motor\Admin\Database\Factories\ClientFactory factory(...$parameters)
 * @method static Builder|Client filteredBy(\Motor\Core\Filter\Filter $filter, $column)
 * @method static Builder|Client filteredByMultiple(\Motor\Core\Filter\Filter $filter)
 * @method static Builder|Client newModelQuery()
 * @method static Builder|Client newQuery()
 * @method static Builder|Client query()
 * @method static Builder|Client search($query, $full_text = false)
 * @method static Builder|Client whereAddress($value)
 * @method static Builder|Client whereCity($value)
 * @method static Builder|Client whereContactEmail($value)
 * @method static Builder|Client whereContactName($value)
 * @method static Builder|Client whereContactPhone($value)
 * @method static Builder|Client whereCountryIso31661($value)
 * @method static Builder|Client whereCreatedAt($value)
 * @method static Builder|Client whereCreatedBy($value)
 * @method static Builder|Client whereDeletedBy($value)
 * @method static Builder|Client whereDescription($value)
 * @method static Builder|Client whereId($value)
 * @method static Builder|Client whereIsActive($value)
 * @method static Builder|Client whereName($value)
 * @method static Builder|Client whereSlug($value)
 * @method static Builder|Client whereUpdatedAt($value)
 * @method static Builder|Client whereUpdatedBy($value)
 * @method static Builder|Client whereWebsite($value)
 * @method static Builder|Client whereZip($value)
 *
 * @mixin \Eloquent
 */
class Client extends Model
{
    use Filterable;
    use HasFactory;
    use HasShortflakePrimary;
    use Searchable;
    use Userstamps;

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'motor_admin_clients_index';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'is_active',
        'name',
        'address',
        'zip',
        'city',
        'country_iso_3166_1',
        'website',
        'contact_name',
        'contact_phone',
        'contact_email',
        'description',
        'frontend_config',
    ];

    protected function casts(): array
    {
        return [
            'frontend_config' => AsArrayObject::class,
        ];
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_client', 'client_id', 'user_id');
    }

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }
}
