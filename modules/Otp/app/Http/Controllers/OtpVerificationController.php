<?php

namespace Modules\Otp\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Otp\Actions\SendOtpAction;
use Modules\Otp\Actions\VerifyOtpAction;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Http\Requests\SendVerificationCodeRequest;
use Modules\Otp\Http\Requests\VerifyOtpRequest;
use Modules\Otp\Http\Resources\VerificationCodeResource;
use Modules\Otp\Models\VerificationCode;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Mrj\Foundation\Support\PhoneNumber;

class OtpVerificationController extends Controller
{
    public function __construct(
        private readonly SendOtpAction $sendOtp,
        private readonly VerifyOtpAction $verifyOtp,
    ) {}

    /**
     * Send a verification code to the provided contact.
     */
    public function sendVerificationCode(SendVerificationCodeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $contactType = ContactType::from($data['contact_type']);
        // Canonicalise to E.164 for phones so the stored code and every lookup
        // use one form (email is left unchanged).
        $contact = $contactType === ContactType::Phone
            ? PhoneNumber::toE164($data['contact'])
            : $data['contact'];

        if (($data['is_registration'] ?? false) && $this->accountExists($contactType, $contact)) {
            return JsonResponseFactory::error('An account with this '.$contactType->value.' already exists.', null, 400);
        }

        $existing = VerificationCode::active()->contact($contact)->latest()->first();

        if ($existing && $existing->created_at->diffInSeconds(now()) < 60) {
            return JsonResponseFactory::error('Please wait 60 seconds before requesting another code.', null, 429);
        }

        $maxAttempts = (int) config('settings.max_verification_attempts.value', 5);

        if (VerificationCode::active()->contact($contact)->count() >= $maxAttempts) {
            return JsonResponseFactory::error('Maximum verification code limit reached. Please try again later.', null, 429);
        }

        $verificationCode = $this->sendOtp->execute($contact, $contactType);

        return JsonResponseFactory::success('Verification code sent.', VerificationCodeResource::make($verificationCode));
    }

    /**
     * Verify the provided OTP code.
     */
    public function verifyCode(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        // Match the canonical contact the code was sent to (email unchanged).
        $contact = PhoneNumber::normalizeContact($data['contact']);

        if (! $this->verifyOtp->execute($contact, $data['code'], true)) {
            return JsonResponseFactory::error('Invalid or expired verification code.', null, 400);
        }

        return JsonResponseFactory::success('Verification code is valid.', true);
    }

    /**
     * Registration duplicate check. Where a phone number lives is the project's
     * decision (see User::scopeWherePhone()).
     */
    private function accountExists(ContactType $contactType, string $contact): bool
    {
        if ($contactType === ContactType::Email) {
            return User::where('email', $contact)->exists();
        }

        return User::query()->wherePhone($contact)->exists();
    }
}
