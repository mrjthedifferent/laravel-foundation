<?php

namespace Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];

        return [
            'id' => $this->id,
            'type' => $this->type,
            'notifiable_type' => $this->notifiable_type,
            'notifiable_id' => $this->notifiable_id,
            // Convenience fields extracted from the JSON data payload
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            // The caller's payload is always a JSON object: an empty PHP array would otherwise
            // be sent as [] and break clients that read data.data as a map.
            'data' => [...$data, 'data' => (object) ($data['data'] ?? [])],
            'is_read' => $this->read(),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
