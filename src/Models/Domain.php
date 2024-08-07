<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kra8\Snowflake\HasShortflakePrimary;
use Laravel\Scout\Searchable;
use Motor\Admin\Database\Factories\DomainFactory;
use Motor\Builder\Models\SearchConfig;
use Motor\Builder\Models\SeoRedirect;
use Motor\Core\Traits\Filterable;
use RichanFongdasen\EloquentBlameable\BlameableTrait;

/**
 * Motor\Admin\Models\Domain
 *
 * @property int $id
 * @property int $client_id
 * @property int $is_active
 * @property string $name
 * @property string $protocol
 * @property string $host
 * @property int $port
 * @property string $path
 * @property int $created_by
 * @property int $updated_by
 * @property int|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
class Domain extends Model
{
    use BlameableTrait;
    use Searchable;
    use Filterable;
    use HasFactory;
    use HasShortflakePrimary;

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'motor_admin_domains_index';
    }

    public function toSearchableArray(): array
    {
        $array = [
            'client_id' => $this->client_id,
            'client.name' =>$this->client->name,
            'name'     => $this->name,
            'protocol' => $this->protocol,
            'host'     => $this->host,
            'port'     => $this->port,
            'path'     => $this->path,
            'is_active' => $this->is_active,
        ];

        // Customize the data array...

        return $array;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'client_id',
        'name',
        'protocol',
        'host',
        'port',
        'path',
        'is_active',
    ];

    protected static function newFactory(): DomainFactory
    {
        return DomainFactory::new();
    }

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(config('motor-admin.models.client'));
    }

    public function searchConfigs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SearchConfig::class);
    }

    public function redirections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SeoRedirect::class);
    }
}
