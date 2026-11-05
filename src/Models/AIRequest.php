<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Motor\Admin\Models\AIRequest
 *
 * @property int $id
 * @property int|null $client_id
 * @property int|null $user_id
 * @property string $task
 * @property string $provider
 * @property string|null $model
 * @property string $status
 * @property int $prompt_tokens
 * @property int $completion_tokens
 * @property string $cost_usd
 * @property int $duration_ms
 * @property int $repair_rounds
 * @property string|null $error
 * @property string|null $prompt_hash
 * @property string|null $prompt_text
 */
class AIRequest extends Model
{
    protected $table = 'ai_requests';

    protected $fillable = [
        'client_id',
        'user_id',
        'task',
        'provider',
        'model',
        'status',
        'prompt_tokens',
        'completion_tokens',
        'cost_usd',
        'duration_ms',
        'repair_rounds',
        'error',
        'prompt_hash',
        'prompt_text',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens'     => 'integer',
            'completion_tokens' => 'integer',
            'duration_ms'       => 'integer',
            'repair_rounds'     => 'integer',
        ];
    }
}
