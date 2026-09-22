<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature;

use Mrj\Foundation\Foundation;
use Mrj\Foundation\Tests\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * konekt/html's Form:: facade is gone (see CHANGELOG 0.18.0) — every view
 * uses the <x-form.*> component suite instead. This fails the build if a
 * Form:: call ever creeps back into a Blade view, in this package or in a
 * module's stubs.
 */
class NoFormFacadeUsageTest extends TestCase
{
    public function test_no_blade_view_calls_the_form_facade(): void
    {
        $offenders = [];

        $finder = (new Finder)
            ->files()
            ->in(Foundation::path())
            ->exclude(['vendor', 'node_modules'])
            ->name('*.blade.php');

        foreach ($finder as $file) {
            $contents = $file->getContents();

            if (preg_match('/\bForm::/', $contents)) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders, 'Form:: found in: '.implode(', ', $offenders));
    }
}
