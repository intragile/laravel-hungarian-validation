<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

use IntrAgile\HungarianValidation\Support\Checksum;
use IntrAgile\HungarianValidation\Support\Normalizer;

/**
 * Hungarian IBAN.
 *
 * 28 characters: "HU", two ISO 7064 MOD 97-10 check digits and the 24-digit
 * domestic bank account number, whose GIRO check digits are verified as well.
 * Input is case-insensitive.
 */
final readonly class Iban implements Identifier
{
    private const string TYPE = 'IBAN';

    private const string COUNTRY = 'HU';

    private const int LENGTH = 28;

    private const int BBAN_LENGTH = 24;

    private function __construct(private string $value) {}

    public static function parse(string $value): static
    {
        $violation = self::validate($value);

        if ($violation !== null) {
            throw new InvalidIdentifierException($violation, self::TYPE);
        }

        $iban = strtoupper(Normalizer::compact($value));

        return new self($iban);
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
        $iban = strtoupper(Normalizer::compact($value));

        if (strlen($iban) !== self::LENGTH || preg_match('/^HU\d{26}\z/', $iban) !== 1) {
            return Violation::Format;
        }

        // ISO 13616 issues check digits 02–98 only; 00, 01 and 99 alias valid ones under MOD 97.
        $checkDigits = (int) substr($iban, 2, 2);

        if ($checkDigits < 2 || $checkDigits > 98 || Checksum::mod97(substr($iban, 4).substr($iban, 0, 4)) !== 1) {
            return Violation::Checksum;
        }

        // The BBAN is 24 digits here, so the only rule it can still break is GIRO.
        if (BankAccountNumber::validate(substr($iban, 4)) !== null) {
            return Violation::Checksum;
        }

        return null;
    }

    /**
     * Builds the IBAN of a domestic account; a 16-digit number is padded with zeros.
     */
    public static function fromBankAccount(BankAccountNumber $account): self
    {
        $bban = str_pad($account->value(), self::BBAN_LENGTH, '0');
        $check = 98 - Checksum::mod97($bban.self::COUNTRY.'00');

        return new self(self::COUNTRY.sprintf('%02d', $check).$bban);
    }

    /**
     * The embedded 24-digit domestic account number.
     */
    public function bankAccount(): BankAccountNumber
    {
        return BankAccountNumber::parse(substr($this->value, 4));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function format(): string
    {
        return implode(' ', str_split($this->value, 4));
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
