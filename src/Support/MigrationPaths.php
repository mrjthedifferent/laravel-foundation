<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

use Mrj\Foundation\Enums\ModuleContext;

/**
 * Every migration directory the foundation and the enabled modules own, by
 * the context they belong to. A tenancy library's "migrate every tenant"
 * command is pointed at for(ModuleContext::Tenant), which returns the
 * universal directories plus the tenant ones.
 *
 * @api
 */
final class MigrationPaths
{
    /** @var array<string, list<string>> context value => directories */
    private array $paths = [];

    public function register(ModuleContext $context, string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $path = (string) realpath($path);

        if (! in_array($path, $this->paths[$context->value] ?? [], true)) {
            $this->paths[$context->value][] = $path;
        }
    }

    /**
     * The directories whose migrations run in a database of this context:
     * universal first, then the context's own.
     *
     * @return list<string>
     */
    public function for(ModuleContext $context): array
    {
        $universal = $this->paths[ModuleContext::Universal->value] ?? [];

        if ($context === ModuleContext::Universal) {
            return $universal;
        }

        return [...$universal, ...($this->paths[$context->value] ?? [])];
    }
}
