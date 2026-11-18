<?php
declare(strict_types=1);

namespace App\Test\TestCase\Architecture;

use Cake\TestSuite\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * An all-development scope is the widest thing the application can hold, so only the authorization layer may build
 * one (from a super-administrator assignment). Any other call site under `src` is a way to bypass the scope.
 */
class DevelopmentScopeConstructionTest extends TestCase
{
    public function testAllDevelopmentsScopeIsOnlyBuiltInTheAuthorizationNamespace(): void
    {
        $src = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'src';
        $authorization = $src . DIRECTORY_SEPARATOR . 'Authorization' . DIRECTORY_SEPARATOR;
        $offenders = [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = $file->getPathname();
            if ($file->getExtension() !== 'php' || str_starts_with($path, $authorization)) {
                continue;
            }
            if (preg_match('/DevelopmentScope\s*::\s*all\s*\(/', (string)file_get_contents($path)) === 1) {
                $offenders[] = substr($path, strlen($src) + 1);
            }
        }

        $this->assertSame([], $offenders, 'DevelopmentScope::all() may only be built in src/Authorization.');
    }
}
