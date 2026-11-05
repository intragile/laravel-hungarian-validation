<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

use IntrAgile\HungarianValidation\Support\Checksum;
use IntrAgile\HungarianValidation\Support\Normalizer;

/**
 * Hungarian domestic bank account number (pénzforgalmi jelzőszám, GIRO).
 *
 * 16 or 24 digits. Under 35/2017. (XII. 14.) MNB rendelet, annex 1, digits are
 * weighted 9, 7, 3, 1 cyclically, and two groups must each sum to a multiple of
 * 10: digits 1 to 8, and the rest of the number (digits 9 to 16 or 9 to 24) as a
 * single group. Digit 16 of a 24-digit number is therefore not a check digit.
 */
final readonly class BankAccountNumber implements Identifier
{
    private const string TYPE = 'bank account number';

    private const int GROUP = 8;

    /**
     * @var list<int>
     */
    private const array LENGTHS = [16, 24];

    /**
     * @param  non-empty-string  $value
     */
    private function __construct(private string $value) {}

    public static function parse(string $value): static
    {
        $violation = self::validate($value);

        if ($violation !== null) {
            throw new InvalidIdentifierException($violation, self::TYPE);
        }

        /** @var non-empty-string $digits */
        $digits = Normalizer::compact($value);

        return new self($digits);
    }

    public static function tryParse(string $value): ?static
    {
        try {
            return self::parse($value);
        } catch (InvalidIdentifierException) {
            return null;
        }
    }

    public static function isValid(string $value): bool
    {
        return self::validate($value) === null;
    }

    public static function validate(string $value): ?Violation
    {
        $digits = Normalizer::compact($value);

        if (preg_match('/^\d+\z/', $digits) !== 1) {
            return Violation::Format;
        }

        if (! in_array(strlen($digits), self::LENGTHS, true)) {
            return Violation::Length;
        }

        if (! Checksum::giroGroupValid(substr($digits, 0, self::GROUP))
            || ! Checksum::giroGroupValid(substr($digits, self::GROUP))) {
            return Violation::Checksum;
        }

        return null;
    }

    /**
     * The number of digits: 16 or 24.
     */
    public function length(): int
    {
        return strlen($this->value);
    }

    /**
     * The three-digit bank identifier.
     */
    public function bankCode(): string
    {
        return substr($this->value, 0, 3);
    }

    /**
     * The four-digit branch identifier (digits 4 to 7).
     */
    public function branchCode(): string
    {
        return substr($this->value, 3, 4);
    }

    /**
     * The digits as given; a trailing all-zero block is kept.
     */
    public function value(): string
    {
        return $this->value;
    }

    public function format(): string
    {
        return implode('-', str_split($this->value, self::GROUP));
    }

    public function toIban(): Iban
    {
        return Iban::fromBankAccount($this);
    }

    public function equals(Identifier $other): bool
    {
        return $other instanceof self && $other->value() === $this->value;
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
