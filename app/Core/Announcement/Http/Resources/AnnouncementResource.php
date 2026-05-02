<?php

namespace App\Core\Announcement\Http\Resources;

use App\Core\Announcement\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Announcement
 */
class AnnouncementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type->value,
            'is_dismissable' => $this->is_dismissable,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
