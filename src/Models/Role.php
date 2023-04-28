<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Kra8\Snowflake\HasShortflakePrimary;
use Motor\Core\Traits\Filterable;
use Motor\Core\Traits\Searchable;

/**
 * Motor\Admin\Models\Role
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Motor\Admin\Models\Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Motor\Admin\Models\User[] $users
 * @property-read int|null $users_count
 *
 * @method static Builder|Role filteredBy(\Motor\Core\Filter\Filter $filter, $column)
 * @method static Builder|Role filteredByMultiple(\Motor\Core\Filter\Filter $filter)
 * @method static Builder|Role newModelQuery()
 * @method static Builder|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role permission($permissions)
 * @method static Builder|Role query()
 * @method static Builder|Role search($query, $full_text = false)
 * @method static Builder|Role whereCreatedAt($value)
 * @method static Builder|Role whereGuardName($value)
 * @method static Builder|Role whereId($value)
 * @method static Builder|Role whereName($value)
 * @method static Builder|Role whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Role extends \Spatie\Permission\Models\Role
{
    use Searchable;
    use Filterable;
    use HasShortflakePrimary;

    /**
     * Searchable columns for the searchable trait
     *
     * @var array
     */
    protected array $searchableColumns = [
        'name',
        'guard_name',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];
}
