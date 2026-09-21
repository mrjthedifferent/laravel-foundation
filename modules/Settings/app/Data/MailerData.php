<?php

namespace Modules\Settings\Data;

use Illuminate\Validation\Rule;
use Modules\Settings\Services\MailerSecretCipher;
use Spatie\LaravelData\Data;

/**
 * Canonical shape of a single email mailer. It is the single source of truth for
 * which fields each transport carries, which of those are secret, the per-
 * transport validation rules, and how a mailer is pruned + encrypted for
 * storage. This keeps the stored VALUE free of the "union of every transport's
 * fields" bloat.
 */
class MailerData extends Data
{
    public const TRANSPORTS = ['smtp', 'microsoft_oauth', 'microsoft_graph', 'sendmail', 'log', 'array'];

    public function __construct(
        public string $name,
        public string $transport,
        public MailerFromData $from,
        public ?string $host = null,
        public int|string|null $port = null,
        public ?string $encryption = null,
        public ?string $username = null,
        public ?string $password = null,
        public ?string $tenant_id = null,
        public ?string $client_id = null,
        public ?string $client_secret = null,
        public ?string $mailbox = null,
    ) {}

    /**
     * Build from a stored/validated `{TYPE, VALUE}` mailer entry.
     *
     * @param  array<string, mixed>  $mailer
     */
    public static function fromEntry(array $mailer): self
    {
        $value = is_array($mailer['VALUE'] ?? null) ? $mailer['VALUE'] : [];
        $from = is_array($value['from'] ?? null) ? $value['from'] : [];

        return new self(
            name: (string) ($mailer['TYPE'] ?? ''),
            transport: (string) ($value['transport'] ?? ''),
            from: new MailerFromData(
                address: (string) ($from['address'] ?? ''),
                name: (string) ($from['name'] ?? ''),
            ),
            host: $value['host'] ?? null,
            port: $value['port'] ?? null,
            encryption: $value['encryption'] ?? null,
            username: $value['username'] ?? null,
            password: $value['password'] ?? null,
            tenant_id: $value['tenant_id'] ?? null,
            client_id: $value['client_id'] ?? null,
            client_secret: $value['client_secret'] ?? null,
            mailbox: $value['mailbox'] ?? null,
        );
    }

    /**
     * The VALUE keys a transport actually uses (besides transport + from).
     *
     * @return array<int, string>
     */
    public static function transportFields(string $transport): array
    {
        return match ($transport) {
            'smtp' => ['host', 'port', 'encryption', 'username', 'password'],
            'microsoft_oauth', 'microsoft_graph' => ['tenant_id', 'client_id', 'client_secret', 'mailbox'],
            default => [],
        };
    }

    /**
     * The secret VALUE keys a transport stores encrypted at rest.
     *
     * @return array<int, string>
     */
    public static function secretFields(string $transport): array
    {
        return match ($transport) {
            'smtp' => ['password'],
            'microsoft_oauth', 'microsoft_graph' => ['client_secret'],
            default => [],
        };
    }

    /**
     * Prune to exactly the transport's fields and encrypt its secrets. A blank
     * secret keeps the previously stored one (secrets are never rendered back
     * into the form); any stored plaintext is opportunistically encrypted.
     *
     * @param  array<string, mixed>  $storedValue  the previously stored VALUE for this mailer
     * @return array{TYPE: string, VALUE: array<string, mixed>}
     */
    public function toEntry(MailerSecretCipher $cipher, array $storedValue = []): array
    {
        $value = ['transport' => $this->transport];

        $secrets = self::secretFields($this->transport);

        foreach (self::transportFields($this->transport) as $field) {
            if (in_array($field, $secrets, true)) {
                $incoming = $this->{$field};
                $value[$field] = ($incoming === null || $incoming === '')
                    ? $cipher->encrypt($storedValue[$field] ?? null)
                    : $cipher->encrypt($incoming);

                continue;
            }

            $value[$field] = $this->{$field};
        }

        $value['from'] = ['address' => $this->from->address, 'name' => $this->from->name];

        return ['TYPE' => $this->name, 'VALUE' => $value];
    }

    /**
     * Decrypt the secret fields of a raw stored VALUE so it can be spread into
     * runtime mail config.
     *
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    public static function decryptValue(array $value, MailerSecretCipher $cipher): array
    {
        foreach (self::secretFields((string) ($value['transport'] ?? '')) as $field) {
            if (isset($value[$field])) {
                $value[$field] = $cipher->decrypt($value[$field]);
            }
        }

        return $value;
    }

    /**
     * The shared rules for one mailer at the given path prefix (e.g.
     * "email_mailers.0."). Every possible key is declared so validated() keeps
     * the fields even when the transport does not require them.
     *
     * @return array<string, mixed>
     */
    public static function baseRules(string $prefix): array
    {
        return [
            $prefix.'TYPE' => ['required', 'string', 'max:100'],
            $prefix.'VALUE' => ['required', 'array'],
            $prefix.'VALUE.transport' => ['required', 'string', Rule::in(self::TRANSPORTS)],
            $prefix.'VALUE.host' => ['nullable', 'string', 'max:255'],
            $prefix.'VALUE.port' => ['nullable'],
            $prefix.'VALUE.encryption' => ['nullable', 'string', 'max:20'],
            $prefix.'VALUE.username' => ['nullable', 'string', 'max:255'],
            $prefix.'VALUE.password' => ['nullable', 'string', 'max:255'],
            $prefix.'VALUE.tenant_id' => ['nullable', 'string', 'max:255'],
            $prefix.'VALUE.client_id' => ['nullable', 'string', 'max:255'],
            $prefix.'VALUE.client_secret' => ['nullable', 'string', 'max:500'],
            $prefix.'VALUE.mailbox' => ['nullable', 'string', 'max:255'],
            $prefix.'VALUE.from' => ['required', 'array'],
            $prefix.'VALUE.from.address' => ['required', 'email', 'max:255'],
            $prefix.'VALUE.from.name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * The transport-specific "required" rules layered on top of baseRules. The
     * secret is intentionally left nullable (blank = keep the stored one).
     *
     * @return array<string, mixed>
     */
    public static function rulesFor(string $transport, string $prefix): array
    {
        return match ($transport) {
            'smtp' => [
                $prefix.'VALUE.host' => ['required', 'string', 'max:255'],
                $prefix.'VALUE.port' => ['required', 'integer', 'between:1,65535'],
            ],
            'microsoft_oauth', 'microsoft_graph' => [
                $prefix.'VALUE.tenant_id' => ['required', 'string', 'max:255'],
                $prefix.'VALUE.client_id' => ['required', 'string', 'max:255'],
                $prefix.'VALUE.mailbox' => ['required', 'email', 'max:255'],
            ],
            default => [],
        };
    }
}
