<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel\Rules;

use IntrAgile\HungarianValidation\PostalCode;
use IntrAgile\HungarianValidation\Violation;

/**
 * Validates a Hungarian postal code (irányítószám), optionally restricted to Budapest.
 */
final class HungarianPostalCode extends IdentifierRule
{
    public function __construct(private readonly bool $budapestOnly = false) {}

    public static function type(): string
    {
        return 'postal_code';
    }

    public static function violations(): array
    {
        return [Violation::Format, Violation::Range, Violation::NotAllowed];
    }

    protected function check(string $value): ?Violation
    {
        $violation = PostalCode::validate($value);

        if ($violation !== null) {
            return $violation;
        }

        if ($this->budapestOnly && ! PostalCode::parse($value)->isBudapest()) {
            return Violation::NotAllowed;
        }

        return null;
    }
}
