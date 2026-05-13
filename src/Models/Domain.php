<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kra8\Snowflake\HasShortflakePrimary;
use Laravel\Scout\Searchable;
use Mattiverse\Userstamps\Traits\Userstamps;
use Motor\Admin\Database\Factories\DomainFactory;
use Motor\Admin\Traits\HasEntityConfigurations;
use Motor\Builder\Models\SearchConfig;
use Motor\Builder\Models\SeoRedirect;
use Motor\Core\Traits\Filterable;

/**
 * Motor\Admin\Models\Domain
 *
 * @property int $id
 * @property int $client_id
 * @property bool $is_active
 * @property string $name
 * @property string $protocol
 * @property string $host
 * @property int $port
 * @property string $path
 * @property int $created_by
 * @property int $updated_by
 * @property int|null $deleted_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Client|null $client
 * @property-read Collection|SearchConfig[] $searchConfigs
 * @property-read int|null $search_configs_count
 * @property-read Collection|SeoRedirect[] $redirections
 * @property-read int|null $redirections_count
 *
 * @mixin \Eloquent
 */
class Domain extends Model
{
    use Filterable;
    use HasEntityConfigurations;
    use HasFactory;
    use HasShortflakePrimary;
    use Searchable;
    use Userstamps;

    protected static function booted(): void
    {
        static::saving(function (Domain $domain) {
            if (! $domain->isDirty('is_preview_domain')) {
                return;
            }

            if ($domain->is_preview_domain !== true) {
                return;
            }

            DB::transaction(function () use ($domain) {
                $query = static::query()
                    ->where('client_id', $domain->client_id)
                    ->where('is_preview_domain', true);

                if ($domain->exists) {
                    $query->whereKeyNot($domain->getKey());
                }

                $query->update(['is_preview_domain' => false]);
            });
        });

        static::saving(function (Domain $domain) {
            if (! $domain->isDirty('is_canonical')) {
                return;
            }

            if ($domain->is_canonical !== true) {
                return;
            }

            DB::transaction(function () use ($domain) {
                $query = static::query()
                    ->where('client_id', $domain->client_id)
                    ->where('is_canonical', true);

                if ($domain->exists) {
                    $query->whereKeyNot($domain->getKey());
                }

                $query->update(['is_canonical' => false]);
            });
        });

        // When the canonical Domain (or fields that compose the canonical URL)
        // changes, every cached page for the client now carries a stale
        // `<link rel=canonical>`. Re-dispatch the per-page cache rebuild.
        //
        // The auto-flip above uses a bulk Query Builder `update()`, which
        // bypasses model events — so the *unflipped* sibling does NOT trigger
        // its own `saved` event. Only the originally-saved Domain reaches this
        // observer, which dispatches exactly one wave of rebuild jobs.
        static::saved(function (Domain $domain) {
            // wasChanged() returns false on fresh inserts in Laravel — it only
            // tracks changes from saved state on updates. For a newly-created
            // canonical Domain we still need to invalidate caches, so we treat
            // `wasRecentlyCreated && is_canonical` as a relevant trigger too.
            if ($domain->wasRecentlyCreated) {
                $relevant = $domain->is_canonical;
            } else {
                $flagChanged = $domain->wasChanged('is_canonical');
                $canonicalUrlAffected = $domain->is_canonical
                    && $domain->wasChanged(['host', 'port', 'protocol', 'is_active']);
                $relevant = $flagChanged || $canonicalUrlAffected;
            }

            if (! $relevant) {
                return;
            }

            \Motor\Builder\Models\BuilderPage::query()
                ->where('client_id', $domain->client_id)
                ->where('is_published', true)
                ->select('id')
                ->chunkById(100, function ($pages) {
                    foreach ($pages as $page) {
                        \Motor\Builder\Jobs\RebuildPageCacheJob::dispatch(
                            $page->id,
                            \Motor\Builder\Models\BuilderPage::class,
                            true,
                            true,
                        );
                    }
                });
        });
    }

    public static function canonicalFor(int $clientId): ?self
    {
        return static::query()
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->orderByDesc('is_canonical')
            ->orderBy('id')
            ->first();
    }

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
            'client_id'   => $this->client_id,
            'client.name' => $this->client->name,
            'name'        => $this->name,
            'protocol'    => $this->protocol,
            'host'        => $this->host,
            'port'        => $this->port,
            'path'        => $this->path,
            'is_active'   => $this->is_active,
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
        'is_preview_domain',
        'is_canonical',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_active' => 'boolean',
            'is_preview_domain' => 'boolean',
            'is_canonical' => 'boolean',
        ];
    }

    public function scopeActiveDomainByHostPortScheme(Builder $query, string $host, int $port, string $schema): Builder
    {
        return $query->where('is_active', true)
            ->where('host', $host)
            ->where('port', $port)
            ->where('protocol', $schema);
    }

    protected static function newFactory(): DomainFactory
    {
        return DomainFactory::new();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(config('motor-admin.models.client'));
    }

    public function searchConfigs(): HasMany
    {
        return $this->hasMany(SearchConfig::class);
    }

    public function redirections(): HasMany
    {
        return $this->hasMany(SeoRedirect::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
