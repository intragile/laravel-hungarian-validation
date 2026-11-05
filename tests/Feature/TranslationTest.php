<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature;

use FilesystemIterator;
use IntrAgile\HungarianValidation\Laravel\Rules\IdentifierRule;
use IntrAgile\HungarianValidation\Laravel\StringRules;
use IntrAgile\HungarianValidation\Tests\TestCase;
use IntrAgile\HungarianValidation\Violation;
use ReflectionClass;
use SplFileInfo;

final class TranslationTest extends TestCase
{
    private const array LOCALES = ['en', 'hu'];

    /**
     * Every concrete rule class in src/Laravel/Rules.
     *
     * @return list<class-string<IdentifierRule>>
     */
    private static function ruleClasses(): array
    {
        $classes = [];

        /** @var SplFileInfo $file */
        foreach (new FilesystemIterator(dirname(__DIR__, 2).'/src/Laravel/Rules') as $file) {
            $class = 'IntrAgile\\HungarianValidation\\Laravel\\Rules\\'.$file->getBasename('.php');

            if (! class_exists($class) || ! is_subclass_of($class, IdentifierRule::class)) {
                continue;
            }

            if (! (new ReflectionClass($class))->isAbstract()) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    public function test_every_violation_has_translations(): void
    {
        $classes = self::ruleClasses();
        self::assertCount(8, $classes);

        foreach ($classes as $class) {
            self::assertContains(Violation::Format, $class::violations(), $class);

            foreach ($class::violations() as $violation) {
                self::assertContains($violation, Violation::cases());

                foreach (self::LOCALES as $locale) {
                    $key = $class::messageKey($violation);
                    $message = trans($key, ['attribute' => 'field'], $locale);

                    self::assertTrue(trans()->hasForLocale($key, $locale), "{$key} is missing in {$locale}.");
                    self::assertIsString($message);
                    self::assertStringContainsString('field', $message, "{$key} ({$locale}) has no :attribute placeholder.");
                }
            }
        }
    }

    public function test_language_files_contain_exactly_the_reachable_keys(): void
    {
        $expected = [];

        foreach (self::ruleClasses() as $class) {
            foreach ($class::violations() as $violation) {
                $expected[] = $class::type().'.'.$violation->value;
            }
        }

        sort($expected);

        foreach (self::LOCALES as $locale) {
            /** @var array<string, array<string, string>> $lines */
            $lines = require dirname(__DIR__, 2)."/lang/{$locale}/validation.php";
            $actual = [];

            foreach ($lines as $type => $messages) {
                foreach ($messages as $violation => $message) {
                    $actual[] = $type.'.'.$violation;
                    self::assertStringContainsString(':attribute', $message, "{$locale}: {$type}.{$violation}");
                }
            }

            sort($actual);
            self::assertSame($expected, $actual, $locale);
        }
    }

    public function test_hungarian_messages_follow_the_laravel_convention(): void
    {
        /** @var array<string, array<string, string>> $lines */
        $lines = require dirname(__DIR__, 2).'/lang/hu/validation.php';

        foreach ($lines as $type => $messages) {
            foreach ($messages as $violation => $message) {
                self::assertStringStartsWith('A(z) :attribute ', $message, "{$type}.{$violation}");
            }
        }
    }

    public function test_every_string_rule_has_a_rule_class(): void
    {
        self::assertSame(self::ruleClasses(), self::sorted(array_values(StringRules::rules())));

        foreach (StringRules::rules() as $name => $class) {
            self::assertSame('hu_'.$class::type(), $name);
        }
    }

    /**
     * @param  list<class-string<IdentifierRule>>  $classes
     * @return list<class-string<IdentifierRule>>
     */
    private static function sorted(array $classes): array
    {
        sort($classes);

        return $classes;
    }
}
