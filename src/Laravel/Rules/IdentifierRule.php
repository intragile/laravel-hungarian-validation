<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use IntrAgile\HungarianValidation\Violation;

/**
 * Base class of the identifier validation rules.
 *
 * Strings and non-negative integers are validated; integers are converted to
 * their decimal string form without zero-padding. Any other type fails with
 * {@see Violation::Format}. The failure message is the translation
 * `hungarian-validation::validation.{type}.{violation}`.
 */
abstract class IdentifierRule implements ValidationRule
{
    public const string TRANSLATION_NAMESPACE = 'hungarian-validation';

    /**
     * The translation group of the identifier, e.g. "tax_number".
     */
    abstract public static function type(): string;

    /**
     * Every violation the rule can report.
     *
     * @return non-empty-list<Violation>
     */
    abstract public static function violations(): array;

    /**
     * The attribute under validation, used to resolve wildcard field references.
     */
    protected string $attribute = '';

    /**
     * Returns the first rule the string value breaks, or null when it passes.
     */
    abstract protected function check(string $value): ?Violation;

    final public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $violation = $this->violationFor($value, $attribute);

        if ($violation !== null) {
            $fail(static::messageKey($violation))->translate();
        }
    }

    /**
     * Returns the reason the value fails the rule, or null when it passes.
     */
    final public function violationFor(mixed $value, string $attribute = ''): ?Violation
    {
        $this->attribute = $attribute;

        if (is_int($value)) {
            // A negative number is never an identifier, even though the sign would be stripped as a hyphen.
            if ($value < 0) {
                return Violation::Format;
            }

            $value = (string) $value;
        }

        if (! is_string($value)) {
            return Violation::Format;
        }

        return $this->check($value);
    }

    /**
     * The translation key of the message for the given violation.
     */
    final public static function messageKey(Violation $violation): string
    {
        return self::TRANSLATION_NAMESPACE.'::validation.'.static::type().'.'.$violation->value;
    }
}
