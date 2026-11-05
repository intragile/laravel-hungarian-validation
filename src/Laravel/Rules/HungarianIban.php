<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel\Rules;

use IntrAgile\HungarianValidation\Iban;
use IntrAgile\HungarianValidation\Violation;

/**
 * Validates a Hungarian IBAN, including the GIRO check digits of the account number.
 */
final class HungarianIban extends IdentifierRule
{
    public static function type(): string
    {
        return 'iban';
    }

    public static function violations(): array
    {
        return [Violation::Format, Violation::Checksum];
    }

    protected function check(string $value): ?Violation
    {
        return Iban::validate($value);
    }
}
