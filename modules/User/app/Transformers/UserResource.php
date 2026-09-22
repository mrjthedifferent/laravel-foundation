<?php

namespace Modules\User\Transformers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * User API Resource
 *
 * Transforms User model into a standardized JSON response for APIs.
 * Separates data presentation from business logic.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,

            // Profile data
            'gender' => $this->gender ?? '',
            'image' => $this->image,

            // Contact information
            'email' => $this->email,
            'phone' => $this->phone,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'phone_verified_at' => $this->phone_verified_at?->toDateTimeString(),

            // Status
            'is_active' => $this->is_active,
            'is_super_admin' => (bool) $this->is_super_admin,

            // Social auth (conditionally included)
            $this->mergeWhen($this->provider !== null, [
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
