<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Settings\Data\SmsGatewayData;

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

    public function messages(): array
    {
        return [
            'sms_gateways.required' => 'At least one gateway must be configured.',
            'sms_gateways.*.TYPE.required' => 'Each gateway needs a unique name.',
            'sms_gateways.*.VALUE.endpoint.required' => 'Endpoint is required for a gateway.',
            'sms_gateways.*.VALUE.method.in' => 'Method must be GET or POST.',
            'sms_gateways.*.VALUE.mobile_key.required' => 'Mobile key is required for a gateway.',
            'sms_gateways.*.VALUE.message_key.required' => 'Message key is required for a gateway.',
            'sms_gateway.in' => 'The active gateway must be one of the gateways listed below.',
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
