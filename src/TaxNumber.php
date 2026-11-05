<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

use IntrAgile\HungarianValidation\Support\Checksum;
use IntrAgile\HungarianValidation\Support\Normalizer;

/**
 * Hungarian tax number (adószám), formatted as XXXXXXXX-Y-ZZ.
 *
 * The first eight digits are the base number (törzsszám), whose 8th digit is a
 * check digit: digits 1 to 7 are weighted 9, 7, 3, 1, 9, 7, 3 and the check
 * digit is (10 - sum mod 10) mod 10. The 9th digit is the VAT code and the last
 * two digits are the territorial code.
 */
final readonly class TaxNumber implements Identifier
{
    /**
     * Weights of base number digits 1 to 7.
     *
     * @internal
     *
     * @var list<int>
     */
    public const array BASE_WEIGHTS = [9, 7, 3, 1, 9, 7, 3];

    private const string TYPE = 'tax number';

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

        if (preg_match('/^\d{11}\z/', $digits) !== 1) {
            return Violation::Format;
        }

        if (! self::isValidBaseNumber(substr($digits, 0, 8))) {
            return Violation::Checksum;
        }

        if (VatCode::tryFrom((int) $digits[8]) === null) {
            return Violation::VatCode;
        }

        if (! TaxRegion::isKnown(substr($digits, 9, 2))) {
            return Violation::Region;
        }

        return null;
    }

    /**
     * Whether eight ASCII digits form a base number with a correct check digit.
     */
    public static function isValidBaseNumber(string $eightDigits): bool
    {
        if (preg_match('/^\d{8}\z/', $eightDigits) !== 1) {
            return false;
        }

        $sum = Checksum::weighted(substr($eightDigits, 0, 7), self::BASE_WEIGHTS);

        return self::checkDigit($sum) === (int) $eightDigits[7];
    }

    /**
     * The check digit for a weighted sum of base number digits 1 to 7.
     *
     * @internal
     */
    public static function checkDigit(int $weightedSum): int
    {
        return (10 - $weightedSum % 10) % 10;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function format(): string
    {
        return sprintf('%s-%s-%s', $this->baseNumber(), $this->value[8], $this->regionCode());
    }

    public function equals(Identifier $other): bool
    {
        return $other instanceof self && $other->value() === $this->value;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * The 8-digit base number (törzsszám).
     */
    public function baseNumber(): string
    {
        return substr($this->value, 0, 8);
    }

    public function vatCode(): VatCode
    {
        return VatCode::from((int) $this->value[8]);
    }

    /**
     * The 2-digit territorial code; see TaxRegion for its name.
     */
    public function regionCode(): string
    {
        return substr($this->value, 9, 2);
    }

    /**
     * The EU VAT number derived from the base number.
     *
     * For a VAT group member this is not the group's EU VAT number, which is
     * derived from the group identifier instead.
     */
    public function euVatNumber(): EuVatNumber
    {
        return EuVatNumber::fromBaseNumber($this->baseNumber());
    }
}
