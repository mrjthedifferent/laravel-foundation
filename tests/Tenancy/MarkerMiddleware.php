<?php

namespace Mrj\Foundation\Tests\Tenancy;

use Closure;
use Illuminate\Http\Request;

/**
 * Stands in for a tenancy library's middleware; the tests only check where it is attached.
 */
final class MarkerMiddleware
{
    public function handle(Request $request, Closure $next, string $context = ''): mixed
    {
        return $next($request);
    }
}
