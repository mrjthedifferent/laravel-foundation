<?php

namespace Mrj\Foundation;

use Closure;
use Composer\InstalledVersions;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Mrj\Foundation\Contracts\ErrorReporter;
use Mrj\Foundation\Exceptions\Handler;
use Mrj\Foundation\Http\Middleware\CheckUserIsActive;
use Mrj\Foundation\Http\Middleware\EnsureContactIsVerified;
use Mrj\Foundation\Http\Middleware\EnsureIdempotency;
use Mrj\Foundation\Http\Middleware\EnsurePanelIsAvailable;
use Mrj\Foundation\Http\Middleware\EnsurePasswordIsChanged;
use Mrj\Foundation\Http\Middleware\EnsureTwoFactorIsEnabled;
use Mrj\Foundation\Http\Middleware\OptionalAuthenticateSanctum;
use Mrj\Foundation\Http\Middleware\SlideSanctumTokenExpiry;
use Mrj\Foundation\Models\User;
use Throwable;

/**
 * Entry points a project's bootstrap/app.php and providers call, so those files
 * never name a foundation class directly and stay identical across projects.
 *
 * @api
 */
final class Foundation
{
    public const string PACKAGE = 'mrjthedifferent/laravel-foundation';

    /** @var (Closure(array<string, mixed>, User): ?bool)|null */
    private static ?Closure $sidebarVisibility = null;

    /**
     * For ->withMiddleware(). Foundation defaults and the project's additions
     * must share one closure: a second withMiddleware() call replaces the first.
     *
     * @param  (callable(Middleware): void)|null  $project
     */
    public static function middleware(?callable $project = null): Closure
    {
        return function (Middleware $middleware) use ($project): void {
            // Order matters within a group: each runs after the one before it.
            $groups = [
                'web' => [CheckUserIsActive::class, EnsurePasswordIsChanged::class, EnsureTwoFactorIsEnabled::class, EnsurePanelIsAvailable::class],
                // SlideSanctumTokenExpiry (sliding idle expiry for Sanctum API tokens) is last
                // so it runs after auth:sanctum has resolved the user.
                'api' => [CheckUserIsActive::class, EnsurePasswordIsChanged::class, EnsureTwoFactorIsEnabled::class, SlideSanctumTokenExpiry::class],
            ];

            foreach ($groups as $group => $classes) {
                foreach ($classes as $class) {
                    $middleware->appendToGroup($group, $class);
                }
            }

            $aliases = [
                'verified' => EnsureContactIsVerified::class,
                'optional.auth.sanctum' => OptionalAuthenticateSanctum::class,
                'idempotent' => EnsureIdempotency::class,
            ];

            // Middleware::alias() replaces the alias list rather than adding to it, so the
            // project's aliases are read back and merged over the foundation's.
            $defaults = $middleware->getMiddlewareAliases();

            if ($project !== null) {
                $project($middleware);
            }

            $middleware->alias(array_merge(
                $aliases,
                array_diff_assoc($middleware->getMiddlewareAliases(), $defaults),
            ));
        };
    }

    /**
     * For ->withExceptions().
     *
     * @param  (callable(Exceptions): void)|null  $project
     */
    public static function exceptions(?callable $project = null): Closure
    {
        return function (Exceptions $exceptions) use ($project): void {
            $exceptions->reportable(function (Throwable $e): void {
                app(ErrorReporter::class)->capture($e);
            });

            if ($project !== null) {
                $project($exceptions);
            }
        };
    }

    /**
     * For ->withSingletons(). Pass a subclass of the foundation Handler to customize.
     *
     * @param  class-string<Handler>|null  $handler
     * @return array<class-string, class-string>
     */
    public static function singletons(?string $handler = null): array
    {
        return [ExceptionHandler::class => $handler ?? Handler::class];
    }

    /**
     * Decide sidebar visibility for items carrying project-specific flags.
     * Return true or false to decide, or null to fall back to the permission rule.
     *
     * @param  callable(array<string, mixed>, User): ?bool  $resolver
     */
    public static function sidebarVisibility(callable $resolver): void
    {
        self::$sidebarVisibility = $resolver(...);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function resolveSidebarVisibility(array $item, User $user): ?bool
    {
        return self::$sidebarVisibility === null ? null : (self::$sidebarVisibility)($item, $user);
    }

    public static function path(string $path = ''): string
    {
        return dirname(__DIR__).($path === '' ? '' : DIRECTORY_SEPARATOR.ltrim($path, '/\\'));
    }

    public static function modulesPath(): string
    {
        return self::path('modules');
    }

    public static function uiPath(string $path = ''): string
    {
        return self::path('ui'.($path === '' ? '' : '/'.ltrim($path, '/\\')));
    }

    public static function version(): string
    {
        return InstalledVersions::isInstalled(self::PACKAGE)
            ? (string) (InstalledVersions::getPrettyVersion(self::PACKAGE) ?? 'dev')
            : 'dev';
    }

    /**
     * Version plus source reference, e.g. "v3.0.1@baec3fb7f360". It changes whenever the
     * shipped assets can change (a dev or path install keeps its version string, so the
     * reference is what tells those apart). Stamped by foundation:publish and appended to
     * asset URLs, so browsers drop their cached copies after an update.
     */
    public static function assetVersion(): string
    {
        $reference = InstalledVersions::isInstalled(self::PACKAGE)
            ? InstalledVersions::getReference(self::PACKAGE)
            : null;

        return self::version().($reference ? '@'.substr($reference, 0, 12) : '');
    }
}
