<?php

namespace Mrj\Foundation\Tests\Feature;

use Mrj\Foundation\Foundation;
use Mrj\Foundation\Tests\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * Static regression guards for defects that only show up in production, not
 * in a unit test: they need a real network call, a real cache dump, or a
 * production environment to reproduce. Scanning the source is the reliable
 * way to prove they cannot come back.
 */
class SecurityTest extends TestCase
{
    /**
     * `src/Traits/MyGuzzleClient.php` passed 'verify' => false on every request,
     * disabling TLS verification for the SMS gateway and FCM push calls (every
     * secret and OAuth token those calls carry travelled unverified). The trait
     * is gone; nothing in the package may reintroduce the option anywhere.
     */
    public function test_tls_verification_is_never_disabled(): void
    {
        $offenders = [];

        foreach ($this->phpFiles() as $file) {
            $contents = file_get_contents($file);

            if (preg_match('/[\'"]verify[\'"]\s*=>\s*false/', $contents)) {
                $offenders[] = $file;
            }
        }

        $this->assertSame([], $offenders, "TLS verification must never be disabled:\n".implode("\n", $offenders));
    }

    /**
     * @return list<string>
     */
    private function phpFiles(): array
    {
        $finder = Finder::create()
            ->files()
            ->name('*.php')
            ->in(Foundation::path('src'))
            ->in(Foundation::path('modules'))
            ->exclude('tests');

        $files = [];

        foreach ($finder as $file) {
            $files[] = $file->getPathname();
        }

        return $files;
    }
}
