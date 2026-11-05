<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ArchitectureTest extends TestCase
{
    public function test_core_has_no_framework_imports(): void
    {
        $src = dirname(__DIR__, 2).'/src';
        $offenders = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            $relative = substr($file->getPathname(), strlen($src) + 1);

            if ($file->getExtension() !== 'php' || str_starts_with($relative, 'Laravel/') || str_starts_with($relative, 'Faker/')) {
                continue;
            }

            $code = (string) file_get_contents($file->getPathname());

            if (preg_match('/\\\\?\b(Illuminate|Faker)\\\\/', $code) === 1) {
                $offenders[] = $relative;
            }
        }

        self::assertSame([], $offenders, 'The framework-independent core must not reference Illuminate or Faker.');
    }
}
