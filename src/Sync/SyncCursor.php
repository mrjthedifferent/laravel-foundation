<?php

declare(strict_types=1);

namespace Mrj\Foundation\Sync;

/**
 * The opaque position a client sends back to `sync/pull`: per collection, the
 * `updated_at` and id of the last row it received. Encoded as base64url JSON;
 * clients must treat it as an opaque string.
 *
 * @api
 */
final class SyncCursor
{
    /**
     * @param  array<string, array{0: string, 1: string}>  $positions
     */
    public function __construct(private array $positions = []) {}

    public static function decode(?string $raw): self
    {
        if ($raw === null || $raw === '') {
            return new self;
        }

        $json = base64_decode(strtr($raw, '-_', '+/'), true);
        $data = $json === false ? null : json_decode($json, true);

        if (! is_array($data)) {
            return new self;
        }

        $positions = [];

        foreach ($data as $name => $position) {
            if (is_string($name) && is_array($position) && isset($position[0], $position[1])) {
                $positions[$name] = [(string) $position[0], (string) $position[1]];
            }
        }

        return new self($positions);
    }

    public function encode(): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($this->positions)), '+/', '-_'), '=');
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    public function positionOf(string $name): ?array
    {
        return $this->positions[$name] ?? null;
    }

    public function advance(string $name, string $updatedAt, string $id): void
    {
        $this->positions[$name] = [$updatedAt, $id];
    }
}
