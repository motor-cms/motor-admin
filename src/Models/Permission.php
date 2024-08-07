<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kra8\Snowflake\HasShortflakePrimary;
use Laravel\Scout\Searchable;
use Motor\Admin\Database\Factories\PermissionFactory;
use Motor\Core\Traits\Filterable;

/**
 * Motor\Admin\Models\Permission
 *
 * @property int $id
 * @property int|null $permission_group_id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Motor\Admin\Models\PermissionGroup|null $group
 * @property-read \Illuminate\Database\Eloquent\Collection|Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Motor\Admin\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Motor\Admin\Models\User[] $users
 * @property-read int|null $users_count
 *
 * @method static Builder|Permission filteredBy(\Motor\Core\Filter\Filter $filter, $column)
 * @method static Builder|Permission filteredByMultiple(\Motor\Core\Filter\Filter $filter)
 * @method static Builder|Permission newModelQuery()
 * @method static Builder|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Permission permission($permissions)
 * @method static Builder|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder|Permission role($roles, $guard = null)
 * @method static Builder|Permission search($query, $full_text = false)
 * @method static Builder|Permission whereCreatedAt($value)
 * @method static Builder|Permission whereGuardName($value)
 * @method static Builder|Permission whereId($value)
 * @method static Builder|Permission whereName($value)
 * @method static Builder|Permission wherePermissionGroupId($value)
 * @method static Builder|Permission whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Permission extends \Spatie\Permission\Models\Permission
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
        return 'motor_admin_permissions_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'permission_group_id',
        'name',
        'guard_name',
    ];

    protected static function newFactory(): PermissionFactory
    {
        return PermissionFactory::new();
    }

    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(config('motor-admin.models.permission_group'), 'permission_group_id');
    }
}
