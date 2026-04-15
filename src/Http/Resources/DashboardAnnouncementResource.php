<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Motor\Admin\Models\DashboardAnnouncement;

/**
 * @mixin DashboardAnnouncement
 */
class DashboardAnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'audience' => $this->audience,
            'target_user_ids' => $this->target_user_ids,
            'linkable_type' => $this->linkable_type,
            'linkable_id' => $this->linkable_id,
            'linkable_name' => data_get($this->linkable, 'name')
                ?? data_get($this->linkable, 'title')
                ?? data_get($this->linkable, 'description'),
            'linkable_url' => $this->linkable_url,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_by' => $this->created_by,
            'created_by_name' => data_get($this->creator, 'name'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
