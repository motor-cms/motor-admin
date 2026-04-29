<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kra8\Snowflake\HasShortflakePrimary;
use Laravel\Scout\Searchable;
use Motor\Admin\Database\Factories\PermissionGroupFactory;
use Motor\Core\Traits\Filterable;

/**
 * Motor\Admin\Models\PermissionGroup
 *
 * @property int $id
 * @property string $name
 * @property int|null $sort_position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Permission[] $permissions
 * @property-read int|null $permissions_count
 *
 * @method static Builder|PermissionGroup filteredBy(\Motor\Core\Filter\Filter $filter, $column)
 * @method static Builder|PermissionGroup filteredByMultiple(\Motor\Core\Filter\Filter $filter)
 * @method static Builder|PermissionGroup newModelQuery()
 * @method static Builder|PermissionGroup newQuery()
 * @method static Builder|PermissionGroup query()
 * @method static Builder|PermissionGroup search($query, $full_text = false)
 * @method static Builder|PermissionGroup whereCreatedAt($value)
 * @method static Builder|PermissionGroup whereId($value)
 * @method static Builder|PermissionGroup whereName($value)
 * @method static Builder|PermissionGroup whereSortPosition($value)
 * @method static Builder|PermissionGroup whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PermissionGroup extends Model
{
    use Filterable;
    use HasFactory;
    use HasShortflakePrimary;
    use Searchable;

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'motor_admin_permission_groups_index';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'sort_position',
    ];

    protected static function newFactory(): PermissionGroupFactory
    {
        return PermissionGroupFactory::new();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(config('motor-admin.models.permission'), 'permission_group_id');
    }
}
