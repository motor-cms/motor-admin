<?php

namespace Motor\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardAnnouncementPutRequest extends FormRequest
{
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
            'client_id' => 'nullable|integer|exists:clients,id',
            'linkable_type' => 'nullable|string',
            'linkable_id' => 'nullable|integer',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ];
    }
}
