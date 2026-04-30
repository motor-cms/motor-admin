<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Kra8\Snowflake\HasShortflakePrimary;
use Motor\Core\Traits\Filterable;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Motor\Admin\Models\EntityConfiguration
 *
 * @property int $id
 * @property string $configurable_type
 * @property int $configurable_id
 * @property int $config_variable_id
 * @property string|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read ConfigVariable $configVariable
 * @property-read Model $configurable
 *
 * @method static Builder|EntityConfiguration filteredBy(\Motor\Core\Filter\Filter $filter, $column)
 * @method static Builder|EntityConfiguration filteredByMultiple(\Motor\Core\Filter\Filter $filter)
 * @method static Builder|EntityConfiguration newModelQuery()
 * @method static Builder|EntityConfiguration newQuery()
 * @method static Builder|EntityConfiguration query()
 *
 * @mixin \Eloquent
 */
class EntityConfiguration extends Model
{
    use Userstamps;
    use Filterable;
    use HasFactory;
    use HasShortflakePrimary;

    protected $fillable = [
        'configurable_type',
        'configurable_id',
        'config_variable_id',
        'value',
    ];

    public function configurable(): MorphTo
    {
        return $this->morphTo();
    }

    public function configVariable(): BelongsTo
    {
        return $this->belongsTo(ConfigVariable::class);
    }
}
