<?php

namespace Motor\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Motor\Core\Http\Requests\ValidatesAgainstUserClients;

class DashboardAnnouncementPostRequest extends FormRequest
{
    use ValidatesAgainstUserClients;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'type' => 'required|in:info,warning,error',
            'audience' => 'required|in:self,users,client',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'integer|exists:users,id',
            // Phase 8 follow-up to ZRMDEV-165: SuperAdmin can target any
            // seeded client; everyone else is restricted to their pivot.
            // The controller still falls back to $user->clients->first()?->id
            // when client_id is omitted, but a non-admin caller can no longer
            // poison rows by submitting a foreign client_id explicitly.
            'client_id' => ['nullable', 'integer', 'exists:clients,id', $this->allowedClientIdsRule()],
            'linkable_type' => 'nullable|string',
            'linkable_id' => 'nullable|integer',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ];
    }
}
