<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature;

use Illuminate\Support\ServiceProvider;
use IntrAgile\HungarianValidation\Laravel\HungarianValidationServiceProvider;
use IntrAgile\HungarianValidation\Laravel\StringRules;
use IntrAgile\HungarianValidation\Tests\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function test_language_files_are_publishable(): void
    {
        $paths = ServiceProvider::pathsToPublish(HungarianValidationServiceProvider::class, 'hungarian-validation-lang');

        self::assertCount(1, $paths);
        self::assertSame(realpath(dirname(__DIR__, 2).'/lang'), realpath((string) array_key_first($paths)));
        self::assertSame($this->app?->langPath('vendor/hungarian-validation'), array_values($paths)[0]);
    }

    public function test_string_rules_are_registered(): void
    {
        $validator = validator(['value' => 'x'], ['value' => 'present']);

        foreach (array_keys(StringRules::rules()) as $name) {
            self::assertArrayHasKey($name, $validator->extensions, $name);
        }
    }

    public function test_translations_are_loaded(): void
    {
        self::assertSame(
            'A(z) mező ellenőrzőszáma hibás.',
            trans('hungarian-validation::validation.taj.checksum', ['attribute' => 'mező'], 'hu'),
        );
    }
}
