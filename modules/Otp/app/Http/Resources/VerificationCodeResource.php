<?php

namespace Modules\Otp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class VerificationCodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        // Never expose the raw OTP by default, even in debug. Exposing the code
        // is opt-in via an explicit setting so staging/debug environments do
        // not accidentally leak verification codes over the API.
        $exposeCode = config('app.debug') && (bool) config('settings.otp_expose_code.value', false);

        return [
            'code' => $exposeCode ? $this->code : '********',
            'contact_type' => $this->contact_type,
            'contact' => $this->contact,
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
