<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Tests\TestCase;

final class AppClassesAutoloadTest extends TestCase
{
    /**
     * Provide a dataset for every PHP file under the `app/` directory.
     *
     * @return array<string, array{string, string}>
     */
    public static function appClassProvider(): array
    {
        $basePath = dirname(__DIR__, 2);
        $appPath = realpath($basePath.DIRECTORY_SEPARATOR.'app');

        if ($appPath === false) {
            throw new RuntimeException('Unable to resolve the absolute app path.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($appPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $cases = [];

        foreach ($iterator as $file) {
            $realPath = $file->getRealPath();

            if ($realPath === false || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = mb_substr($realPath, mb_strlen($appPath) + 1);
            $class = 'App\\'.str_replace(['/', '\\'], '\\', Str::beforeLast($relativePath, '.php'));

            $cases[$class] = [$class, $realPath];
        }

        return $cases;
    }

    #[DataProvider('appClassProvider')]
    public function test_app_class_is_autoloadable(string $class, string $path): void
    {
        $autoloadable = class_exists($class) || interface_exists($class) || trait_exists($class);

        $this->assertTrue(
            $autoloadable,
            sprintf('Unable to autoload %s defined in %s', $class, $path)
        );
    }
}
