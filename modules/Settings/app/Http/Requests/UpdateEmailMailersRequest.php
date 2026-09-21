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
            'email_mailers.required' => 'At least one mailer must be configured.',
            'email_mailers.*.TYPE.required' => 'Each mailer needs a unique name.',
            'email_mailers.*.VALUE.transport.in' => 'Select a supported transport for each mailer.',
            'email_mailers.*.VALUE.host.required' => 'Host is required for an SMTP mailer.',
            'email_mailers.*.VALUE.port.required' => 'Port is required for an SMTP mailer.',
            'email_mailers.*.VALUE.port.integer' => 'Port must be a number between 1 and 65535.',
            'email_mailers.*.VALUE.port.between' => 'Port must be a number between 1 and 65535.',
            'email_mailers.*.VALUE.tenant_id.required' => 'Tenant ID is required for a Microsoft 365 (OAuth2) mailer.',
            'email_mailers.*.VALUE.client_id.required' => 'Client ID is required for a Microsoft 365 (OAuth2) mailer.',
            'email_mailers.*.VALUE.client_secret.required' => 'Client Secret is required for a Microsoft 365 (OAuth2) mailer.',
            'email_mailers.*.VALUE.mailbox.required' => 'Mailbox is required for a Microsoft 365 (OAuth2) mailer.',
            'email_mailers.*.VALUE.mailbox.email' => 'Mailbox must be the full email address of the sending mailbox.',
            'email_mailer.in' => 'The active mailer must be one of the mailers listed below.',
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
