<?php

namespace Modules\Settings\Data;

use Illuminate\Validation\Rule;
use Modules\Settings\Services\MailerSecretCipher;
use Spatie\LaravelData\Data;

/**
 * Canonical shape of a single SMS gateway. It absorbs the form's parallel
 * keys[]/values[] header & parameter arrays into associative maps, encrypts
 * every header/param value at rest (any of them can carry an API key or token),
 * and is the single source of the gateway validation rules.
 */
class SmsGatewayData extends Data
{
    public const METHODS = ['GET', 'POST'];

    /**
     * @param  array<string, mixed>  $headers
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public string $name,
        public string $endpoint,
        public string $method,
        public ?string $mobile_prefix,
        public string $mobile_key,
        public string $message_key,
        public array $headers = [],
        public array $params = [],
    ) {}

    /**
     * Build from a stored/submitted `{TYPE, VALUE}` gateway entry, normalizing
     * the form's keys[]/values[] arrays into associative maps.
     *
     * @param  array<string, mixed>  $gateway
     */
    public static function fromEntry(array $gateway): self
    {
        $value = is_array($gateway['VALUE'] ?? null) ? $gateway['VALUE'] : [];

        return new self(
            name: (string) ($gateway['TYPE'] ?? ''),
            endpoint: (string) ($value['endpoint'] ?? ''),
            method: strtoupper((string) ($value['method'] ?? 'POST')),
            mobile_prefix: $value['mobile_prefix'] ?? null,
            mobile_key: (string) ($value['mobile_key'] ?? ''),
            message_key: (string) ($value['message_key'] ?? ''),
            headers: self::toAssoc($value['headers'] ?? []),
            params: self::toAssoc($value['params'] ?? []),
        );
    }

    /**
     * Prune to the gateway's fields and encrypt every header/param value. A
     * blank value keeps the previously stored (encrypted) one for that key, so a
     * masked value survives an unrelated edit.
     *
     * @param  array<string, mixed>  $storedValue
     * @return array{TYPE: string, VALUE: array<string, mixed>}
     */
    public function toEntry(MailerSecretCipher $cipher, array $storedValue = []): array
    {
        $storedHeaders = is_array($storedValue['headers'] ?? null) ? $storedValue['headers'] : [];
        $storedParams = is_array($storedValue['params'] ?? null) ? $storedValue['params'] : [];

        return [
            'TYPE' => $this->name,
            'VALUE' => [
                'endpoint' => $this->endpoint,
                'method' => $this->method,
                'mobile_prefix' => $this->mobile_prefix,
                'mobile_key' => $this->mobile_key,
                'message_key' => $this->message_key,
                'headers' => $this->encryptMap($this->headers, $storedHeaders, $cipher),
                'params' => $this->encryptMap($this->params, $storedParams, $cipher),
            ],
        ];
    }

    /**
     * Decrypt the header/param values of a raw stored VALUE so the gateway can
     * be called.
     *
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    public static function decryptValue(array $value, MailerSecretCipher $cipher): array
    {
        foreach (['headers', 'params'] as $group) {
            if (is_array($value[$group] ?? null)) {
                $value[$group] = array_map(
                    fn ($v) => is_string($v) ? $cipher->decrypt($v) : $v,
                    $value[$group],
                );
            }
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private function encryptMap(array $incoming, array $stored, MailerSecretCipher $cipher): array
    {
        $out = [];
        foreach ($incoming as $key => $value) {
            $out[$key] = ($value === '' || $value === null)
                ? $cipher->encrypt($stored[$key] ?? null)
                : $cipher->encrypt(is_string($value) ? $value : (string) $value);
        }

        return $out;
    }

    /**
     * Convert the form's `{keys:[...], values:[...]}` pair into an associative
     * map. A map that is already associative (loaded from storage) passes
     * through untouched.
     *
     * @param  mixed  $raw
     * @return array<string, mixed>
     */
    private static function toAssoc($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        if (isset($raw['keys'], $raw['values']) && is_array($raw['keys']) && is_array($raw['values'])) {
            $assoc = [];
            foreach ($raw['keys'] as $i => $key) {
                if ($key !== '' && $key !== null) {
                    $assoc[$key] = $raw['values'][$i] ?? '';
                }
            }

            return $assoc;
        }

        return $raw;
    }

    /**
     * Rules for one gateway at the given path prefix (e.g. "sms_gateways.0.").
     * The keys[]/values[] sub-arrays are declared so validated() keeps them.
     *
     * @return array<string, mixed>
     */
    public static function baseRules(string $prefix): array
    {
        return [
            $prefix.'TYPE' => ['required', 'string', 'max:100'],
            $prefix.'VALUE' => ['required', 'array'],
            $prefix.'VALUE.endpoint' => ['required', 'string', 'max:2048'],
            $prefix.'VALUE.method' => ['required', 'string', Rule::in(self::METHODS)],
            $prefix.'VALUE.mobile_prefix' => ['nullable', 'string', 'max:10'],
            $prefix.'VALUE.mobile_key' => ['required', 'string', 'max:100'],
            $prefix.'VALUE.message_key' => ['required', 'string', 'max:100'],
            $prefix.'VALUE.headers' => ['nullable', 'array'],
            $prefix.'VALUE.headers.keys' => ['nullable', 'array'],
            $prefix.'VALUE.headers.keys.*' => ['nullable', 'string', 'max:255'],
            $prefix.'VALUE.headers.values' => ['nullable', 'array'],
            $prefix.'VALUE.headers.values.*' => ['nullable', 'string'],
            $prefix.'VALUE.params' => ['nullable', 'array'],
            $prefix.'VALUE.params.keys' => ['nullable', 'array'],
            $prefix.'VALUE.params.keys.*' => ['nullable', 'string', 'max:255'],
            $prefix.'VALUE.params.values' => ['nullable', 'array'],
            $prefix.'VALUE.params.values.*' => ['nullable', 'string'],
        ];
    }
}
