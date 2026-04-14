<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Kra8\Snowflake\HasShortflakePrimary;
use Motor\Core\Traits\Filterable;
use Mattiverse\Userstamps\Traits\Userstamps;

class DashboardAnnouncement extends Model
{
    use Userstamps;
    use Filterable;
    use HasShortflakePrimary;

    protected $fillable = [
        'title',
        'body',
        'type',
        'audience',
        'target_user_ids',
        'linkable_type',
        'linkable_id',
        'starts_at',
        'expires_at',
        'is_active',
        'client_id',
        'created_by',
    ];

    protected $casts = [
        'target_user_ids' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dismissedByUsers()
    {
        return $this->belongsToMany(User::class, 'dashboard_announcement_dismissals', 'announcement_id', 'user_id')
            ->withPivot('dismissed_at');
    }

    public function scopeVisibleTo($query, User $user)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereDoesntHave('dismissedByUsers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where(function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('audience', 'self')->where('created_by', $user->id);
                })->orWhere(function ($q2) use ($user) {
                    $q2->where('audience', 'users')
                        ->whereJsonContains('target_user_ids', $user->id);
                })->orWhere(function ($q2) use ($user) {
                    $q2->where('audience', 'client')
                        ->whereIn('client_id', $user->clients->pluck('id'));
                });
            });
    }

    public function getLinkableUrlAttribute(): ?string
    {
        if (! $this->linkable_type || ! $this->linkable_id) {
            return null;
        }

        $linkable = $this->linkable;
        if (! $linkable) {
            return null;
        }

        return match ($this->linkable_type) {
            'Motor\\Builder\\Models\\BuilderPage' => "/motor-builder/builder-pages/{$linkable->id}/edit",
            'Motor\\Builder\\Models\\Navigation' => "/motor-builder/navigation-trees/{$linkable->getNavigationTreeId()}/navigation-items/{$linkable->id}/edit",
            'Motor\\Media\\Models\\File' => "/motor-media/files/{$linkable->id}/edit",
            'Motor\\ContentType\\Models\\CustomContentType' => "/motor-content-type/content-types/{$linkable->id}/edit",
            default => null,
        };
    }
}
