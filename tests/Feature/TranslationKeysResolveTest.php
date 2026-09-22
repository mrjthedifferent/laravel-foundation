<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature;

use Mrj\Foundation\Foundation;
use Mrj\Foundation\Tests\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * A namespaced key that doesn't exist renders as the raw key ("user::user.index.titel")
 * instead of failing, so a typo only shows up on screen. This finds every literal
 * key the package references and checks it resolves to a string.
 */
class TranslationKeysResolveTest extends TestCase
{
    public function test_every_referenced_translation_key_exists(): void
    {
        $missing = [];

        $finder = (new Finder)
            ->files()
            ->in([Foundation::path('src'), Foundation::path('ui'), Foundation::modulesPath()])
            ->exclude(['vendor', 'node_modules', 'tests'])
            ->name('*.php');

        foreach ($finder as $file) {
            preg_match_all("/(?:__|trans|@lang)\(\s*'([a-z]+::[\w.]+)'/", $file->getContents(), $matches);

            foreach (array_unique($matches[1]) as $key) {
                $line = __($key);

                if (! is_string($line) || $line === $key) {
                    $missing[] = $file->getRelativePathname().': '.$key;
                }
            }
        }

        $this->assertSame([], $missing, "Unknown translation keys:\n".implode("\n", $missing));
    }
}
