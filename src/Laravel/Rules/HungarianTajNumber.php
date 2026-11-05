<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel\Rules;

use IntrAgile\HungarianValidation\TajNumber;
use IntrAgile\HungarianValidation\Violation;

/**
 * Validates a Hungarian social security number (TAJ-szám).
 */
final class HungarianTajNumber extends IdentifierRule
{
    public static function type(): string
    {
        return 'taj';
    }

    public static function violations(): array
    {
        return [Violation::Format, Violation::Checksum];
    }

    protected function check(string $value): ?Violation
    {
        return TajNumber::validate($value);
    }
}
