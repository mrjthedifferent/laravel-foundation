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
use Modules\Otp\Support\OtpThrottle;
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
            return JsonResponseFactory::error(__('otp::otp.errors.account_exists', ['type' => $contactType->value]), null, 400);
        }

        $refusal = OtpThrottle::refusal($contact);

        if ($refusal !== null) {
            return JsonResponseFactory::error(__($refusal), null, 429);
        }

        $verificationCode = $this->sendOtp->execute($contact, $contactType);

        return JsonResponseFactory::success(__('otp::otp.success.code_sent'), VerificationCodeResource::make($verificationCode));
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
            return JsonResponseFactory::error(__('otp::otp.errors.invalid_or_expired_code'), null, 400);
        }

        return JsonResponseFactory::success(__('otp::otp.success.code_valid'), true);
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
