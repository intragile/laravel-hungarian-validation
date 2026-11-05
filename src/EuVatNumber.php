<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

use IntrAgile\HungarianValidation\Support\Normalizer;

/**
 * Hungarian EU VAT number (közösségi adószám): "HU" followed by the 8-digit
 * base number of the tax number.
 *
 * The country prefix is required and accepted in either case; a bare base
 * number is rejected, as it is not an EU VAT number.
 */
final readonly class EuVatNumber implements Identifier
{
    private const string TYPE = 'EU VAT number';

    private const string PREFIX = 'HU';

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

        return new self(self::PREFIX.substr(Normalizer::compact($value), 2));
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
        $compact = strtoupper(Normalizer::compact($value));

        if (preg_match('/^HU\d{8}\z/', $compact) !== 1) {
            return Violation::Format;
        }

        if (! TaxNumber::isValidBaseNumber(substr($compact, 2))) {
            return Violation::Checksum;
        }

        return null;
    }

    /**
     * @throws InvalidIdentifierException
     */
    public static function fromBaseNumber(string $baseNumber): self
    {
        return self::parse(self::PREFIX.Normalizer::compact($baseNumber));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function format(): string
    {
        return $this->value;
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
     * The 8-digit base number, without the country prefix.
     */
    public function baseNumber(): string
    {
        return substr($this->value, 2);
    }

    /**
     * Whether the tax number shares this EU VAT number's base number.
     *
     * A VAT group's EU VAT number matches the group identifier, not its members.
     */
    public function matches(TaxNumber $taxNumber): bool
    {
        return $taxNumber->baseNumber() === $this->baseNumber();
    }
}
