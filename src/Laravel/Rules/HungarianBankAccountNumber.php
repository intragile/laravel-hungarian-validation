<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel\Rules;

use IntrAgile\HungarianValidation\BankAccountNumber;
use IntrAgile\HungarianValidation\Violation;
use InvalidArgumentException;

/**
 * Validates a Hungarian domestic bank account number (bankszámlaszám),
 * optionally restricted to 16 or 24 digits.
 */
final class HungarianBankAccountNumber extends IdentifierRule
{
    /**
     * @param  int|null  $length  16 or 24 to require that length; null allows both.
     *
     * @throws InvalidArgumentException When the length is not 16 or 24.
     */
    public function __construct(private readonly ?int $length = null)
    {
        if ($length !== null && $length !== 16 && $length !== 24) {
            throw new InvalidArgumentException(sprintf('Bank account numbers have 16 or 24 digits, %d given.', $length));
        }
    }

    public static function type(): string
    {
        return 'bank_account';
    }

    public static function violations(): array
    {
        return [Violation::Format, Violation::Length, Violation::Checksum];
    }

    protected function check(string $value): ?Violation
    {
        $violation = BankAccountNumber::validate($value);

        if ($violation !== null) {
            return $violation;
        }

        if ($this->length !== null && BankAccountNumber::parse($value)->length() !== $this->length) {
            return Violation::Length;
        }

        return null;
    }
}
