<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kra8\Snowflake\HasShortflakePrimary;
use Laravel\Scout\Searchable;
use Motor\Admin\Database\Factories\LanguageFactory;
use Motor\Core\Traits\Filterable;

/**
 * Motor\Admin\Models\Language
 *
 * @property int $id
 * @property string $iso_639_1
 * @property string $english_name
 * @property string $native_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Motor\Admin\Database\Factories\LanguageFactory factory(...$parameters)
 * @method static Builder|Language filteredBy(\Motor\Core\Filter\Filter $filter, $column)
 * @method static Builder|Language filteredByMultiple(\Motor\Core\Filter\Filter $filter)
 * @method static Builder|Language newModelQuery()
 * @method static Builder|Language newQuery()
 * @method static Builder|Language query()
 * @method static Builder|Language search($query, $full_text = false)
 * @method static Builder|Language whereCreatedAt($value)
 * @method static Builder|Language whereEnglishName($value)
 * @method static Builder|Language whereId($value)
 * @method static Builder|Language whereIso6391($value)
 * @method static Builder|Language whereNativeName($value)
 * @method static Builder|Language whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Language extends Model
{
    use Searchable;
    use Filterable;
    use HasFactory;
    use HasShortflakePrimary;

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'motor_admin_languages_index';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'iso_639_1',
        'english_name',
        'native_name',
    ];

    protected static function newFactory(): LanguageFactory
    {
        return LanguageFactory::new();
    }
}
