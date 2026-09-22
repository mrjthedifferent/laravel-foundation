<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Settings\Data\SmsGatewayData;
use Override;

class UpdateSmsGatewaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        $rules = [
            'sms_gateways' => ['required', 'array', 'min:1'],

            // The active gateway must be one of the gateways being saved.
            'sms_gateway' => ['required', 'string', Rule::in($this->submittedGatewayTypes())],
        ];

        foreach ($this->submittedGateways() as $index => $gateway) {
            $rules += SmsGatewayData::baseRules("sms_gateways.{$index}.");
        }

        return $rules;
    }

    #[Override]
    public function messages(): array
    {
        return [
            'sms_gateways.required' => __('settings::settings.errors.sms_gateways_required'),
            'sms_gateways.*.TYPE.required' => __('settings::settings.errors.sms_gateway_type_required'),
            'sms_gateways.*.VALUE.endpoint.required' => __('settings::settings.errors.sms_gateway_endpoint_required'),
            'sms_gateways.*.VALUE.method.in' => __('settings::settings.errors.sms_gateway_method_in'),
            'sms_gateways.*.VALUE.mobile_key.required' => __('settings::settings.errors.sms_gateway_mobile_key_required'),
            'sms_gateways.*.VALUE.message_key.required' => __('settings::settings.errors.sms_gateway_message_key_required'),
            'sms_gateway.in' => __('settings::settings.errors.sms_gateway_active_in'),
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private function submittedGateways(): array
    {
        $gateways = $this->input('sms_gateways');

        if (! is_array($gateways)) {
            return [];
        }

        return array_filter($gateways, 'is_array');
    }

    /**
     * @return array<int, string>
     */
    private function submittedGatewayTypes(): array
    {
        return collect($this->submittedGateways())
            ->pluck('TYPE')
            ->filter(fn ($type) => is_string($type) && $type !== '')
            ->values()
            ->all();
    }
}
