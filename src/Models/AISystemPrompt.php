<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Kra8\Snowflake\HasShortflakePrimary;
use Laravel\Scout\Searchable;
use Motor\Core\Traits\Filterable;

class AISystemPrompt extends Model
{
    use Filterable;
    use HasShortflakePrimary;
    use Searchable;

    protected $table = 'ai_system_prompts';

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'motor_admin_aisystemprompt_index';
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id'     => (int) $this->id,
            'name'   => $this->name,
            'prompt' => $this->prompt,
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'client_id',
        'name',
        'prompt',
    ];

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(config('motor-admin.models.client'));
    }
}
