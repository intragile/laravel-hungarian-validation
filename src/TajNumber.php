<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

use IntrAgile\HungarianValidation\Support\Checksum;
use IntrAgile\HungarianValidation\Support\Normalizer;

/**
 * Hungarian social security number (TAJ szám).
 *
 * Nine digits; the 9th is a check digit defined by 1996. évi XX. törvény,
 * annex 2: digits 1 to 8 are weighted 3 at odd positions and 7 at even
 * positions, and the check digit is the weighted sum mod 10.
 */
final readonly class TajNumber implements Identifier
{
    /**
     * Check digit weights, repeated over digits 1 to 8.
     *
     * @internal
     *
     * @var list<int>
     */
    public const array WEIGHTS = [3, 7];

    private const string TYPE = 'TAJ number';

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

        if (preg_match('/^\d{9}\z/', $digits) !== 1) {
            return Violation::Format;
        }

        if (Checksum::weighted(substr($digits, 0, 8), self::WEIGHTS) % 10 !== (int) $digits[8]) {
            return Violation::Checksum;
        }

        return null;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function format(): string
    {
        return implode(' ', str_split($this->value, 3));
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
