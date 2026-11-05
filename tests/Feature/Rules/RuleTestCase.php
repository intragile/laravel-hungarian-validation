<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use Illuminate\Support\Facades\Validator;
use IntrAgile\HungarianValidation\Laravel\Rules\IdentifierRule;
use IntrAgile\HungarianValidation\Tests\TestCase;
use IntrAgile\HungarianValidation\Violation;

abstract class RuleTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app?->setLocale('en');
    }

    /**
     * Validates the data and returns the first message per attribute.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @return array<string, string>
     */
    protected function errors(array $data, array $rules, array $messages = []): array
    {
        $errors = [];

        foreach (Validator::make($data, $rules, $messages)->errors()->messages() as $attribute => $list) {
            $errors[(string) $attribute] = (string) $list[0];
        }

        return $errors;
    }

    /**
     * The first message for the value, or null when it passes.
     *
     * @param  mixed  $rule  A rule object or string rule.
     */
    protected function message(mixed $value, mixed $rule, string $attribute = 'value'): ?string
    {
        return $this->errors([$attribute => $value], [$attribute => [$rule]])[$attribute] ?? null;
    }

    /**
     * The translated message of a translation key for the given attribute name.
     */
    protected function expected(string $key, string $attribute = 'value'): string
    {
        $message = trans($key, ['attribute' => $attribute]);
        self::assertIsString($message);
        self::assertNotSame($key, $message, "Missing translation: {$key}");

        return $message;
    }

    /**
     * Asserts that the value passes, or fails with the message of the violation.
     *
     * @param  mixed  $rule  A rule object or string rule.
     * @param  class-string<IdentifierRule>  $class
     */
    protected function assertViolation(mixed $value, mixed $rule, ?Violation $violation, string $class): void
    {
        $message = $this->message($value, $rule);

        if ($violation === null) {
            self::assertNull($message);

            return;
        }

        self::assertSame($this->expected($class::messageKey($violation)), $message);
    }
}
