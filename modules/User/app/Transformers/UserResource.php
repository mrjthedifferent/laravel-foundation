<?php

namespace Modules\User\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User API Resource
 *
 * Transforms User model into a standardized JSON response for APIs.
 * Separates data presentation from business logic.
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,

            // Profile data
            'gender' => $this->gender ?? '',
            'image' => $this->image,

            // Contact information ('phone' is null unless the project's user model provides it)
            'email' => $this->email,
            'phone' => $this->phone,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),

            // Status
            'is_active' => $this->is_active,

            // Social auth (conditionally included)
            $this->mergeWhen($this->provider, [
                'provider' => $this->provider,
                'provider_id' => $this->provider_id,
            ]),

            // Roles (conditionally loaded)
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),

            // Documents (conditionally loaded)
            'documents' => UserDocumentResource::collection($this->whenLoaded('documents')),

            // Timestamps
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
