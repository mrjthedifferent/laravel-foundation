<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Settings\Data\MailerData;
use Override;

class UpdateEmailMailersRequest extends FormRequest
{
    /**
     * Transports the Email Mailers page can configure. Sourced from the DTO so
     * the field set, rules, and this list never drift apart.
     */
    public const TRANSPORTS = MailerData::TRANSPORTS;

    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        // The base (wildcard) rules declare every possible key so validated()
        // keeps a mailer's fields even for the transport it is not using.
        $rules = MailerData::baseRules('email_mailers.*.') + [
            'email_mailers' => ['required', 'array', 'min:1'],

            // The active mailer must be one of the mailers being saved, otherwise
            // mail is left silently unconfigured.
            'email_mailer' => ['required', 'string', Rule::in($this->submittedMailerTypes())],
        ];

        // Transport-specific "required" rules are applied per mailer, so a mailer
        // is never judged by the rules of a transport it does not use — the seeded
        // "log" mailer carries port "0000", which no SMTP port rule accepts.
        foreach ($this->submittedMailers() as $index => $mailer) {
            $rules += MailerData::rulesFor($mailer['VALUE']['transport'] ?? '', "email_mailers.{$index}.");
        }

        return $rules;
    }

    #[Override]
    public function messages(): array
    {
        return [
            'email_mailers.required' => __('settings::settings.errors.email_mailers_required'),
            'email_mailers.*.TYPE.required' => __('settings::settings.errors.email_mailer_type_required'),
            'email_mailers.*.VALUE.transport.in' => __('settings::settings.errors.email_mailer_transport_in'),
            'email_mailers.*.VALUE.host.required' => __('settings::settings.errors.email_mailer_host_required'),
            'email_mailers.*.VALUE.port.required' => __('settings::settings.errors.email_mailer_port_required'),
            'email_mailers.*.VALUE.port.integer' => __('settings::settings.errors.email_mailer_port_range'),
            'email_mailers.*.VALUE.port.between' => __('settings::settings.errors.email_mailer_port_range'),
            'email_mailers.*.VALUE.tenant_id.required' => __('settings::settings.errors.email_mailer_tenant_id_required'),
            'email_mailers.*.VALUE.client_id.required' => __('settings::settings.errors.email_mailer_client_id_required'),
            'email_mailers.*.VALUE.client_secret.required' => __('settings::settings.errors.email_mailer_client_secret_required'),
            'email_mailers.*.VALUE.mailbox.required' => __('settings::settings.errors.email_mailer_mailbox_required'),
            'email_mailers.*.VALUE.mailbox.email' => __('settings::settings.errors.email_mailer_mailbox_email'),
            'email_mailer.in' => __('settings::settings.errors.email_mailer_active_in'),
        ];
    }

    /**
     * The submitted mailers, keyed by their original request index.
     *
     * @return array<int|string, array<string, mixed>>
     */
    private function submittedMailers(): array
    {
        $mailers = $this->input('email_mailers');

        if (! is_array($mailers)) {
            return [];
        }

        return array_filter($mailers, 'is_array');
    }

    /**
     * @return array<int, string>
     */
    private function submittedMailerTypes(): array
    {
        return collect($this->submittedMailers())
            ->pluck('TYPE')
            ->filter(fn ($type) => is_string($type) && $type !== '')
            ->values()
            ->all();
    }
}
