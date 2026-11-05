<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

use IntrAgile\HungarianValidation\Support\Normalizer;
use IntrAgile\HungarianValidation\Support\SettlementDirectory;

/**
 * A Hungarian postal code (irányítószám).
 *
 * Validation is structural: accepted values are 1007 (Margitsziget), 1011-1999
 * and 2000-9999. Codes 1240-1999 are Budapest PO box and organisation codes,
 * which belong to the capital but carry no district.
 *
 * The settlement lookup uses the KSH Gazetteer of Hungary (CC BY 4.0, see
 * DATA-LICENSE.md). The gazetteer lists only part of the codes, e.g. not the
 * delivery district codes of Debrecen, Győr, Miskolc, Pécs and Szeged, so it
 * cannot tell whether a code exists; it only matches settlements to the codes
 * it knows.
 */
final readonly class PostalCode implements Identifier
{
    private function __construct(private string $code) {}

    public static function parse(string $value): static
    {
        $violation = self::validate($value);

        if ($violation !== null) {
            throw new InvalidIdentifierException($violation, 'postal code');
        }

        return new self(Normalizer::compact($value));
    }

    public static function tryParse(string $value): ?static
    {
        return self::validate($value) === null ? self::parse($value) : null;
    }

    public static function isValid(string $value): bool
    {
        return self::validate($value) === null;
    }

    public static function validate(string $value): ?Violation
    {
        $compact = Normalizer::compact($value);

        // Explicit ASCII class and \z: \d may match other scripts, $ allows a trailing newline.
        if (preg_match('/^[0-9]{4}\z/', $compact) !== 1) {
            return Violation::Format;
        }

        $number = (int) $compact;

        // Four digits cap the value at 9999, so only the lower edge needs checking.
        if ($number < 1000 || ($number <= 1010 && $number !== 1007)) {
            return Violation::Range;
        }

        return null;
    }

    public function value(): string
    {
        return $this->code;
    }

    /**
     * Hungarian postal codes have no separated form.
     */
    public function format(): string
    {
        return $this->code;
    }

    public function equals(Identifier $other): bool
    {
        return $other instanceof self && $other->value() === $this->value();
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function isBudapest(): bool
    {
        return str_starts_with($this->code, '1');
    }

    /**
     * The Budapest district number (1-23), or null outside the district codes.
     *
     * Digits 2-3 encode the district for 1011-1239. Margitsziget (1007) has its
     * own code but administratively belongs to district XIII.
     */
    public function budapestDistrict(): ?int
    {
        $number = (int) $this->code;

        if ($number === 1007) {
            return 13;
        }

        if ($number >= 1011 && $number <= 1239) {
            return (int) substr($this->code, 1, 2);
        }

        return null;
    }

    /**
     * The settlements the postal code belongs to, sorted.
     *
     * Every Budapest code yields ['Budapest']. An empty list means the
     * gazetteer does not know the code, not that the code does not exist.
     *
     * @return list<string>
     */
    public function settlements(): array
    {
        if ($this->isBudapest()) {
            return ['Budapest'];
        }

        return SettlementDirectory::settlements($this->code) ?? [];
    }

    /**
     * Whether the settlement name belongs to the postal code, ignoring case and
     * surrounding or repeated whitespace.
     *
     * Returns null when the gazetteer does not know the code, so the name can
     * be neither confirmed nor rejected. Budapest codes match "Budapest" only,
     * not district names.
     */
    public function matchesSettlement(string $name): ?bool
    {
        $settlements = $this->isBudapest() ? ['Budapest'] : SettlementDirectory::settlements($this->code);

        if ($settlements === null) {
            return null;
        }

        foreach ($settlements as $settlement) {
            if (SettlementDirectory::sameName($settlement, $name)) {
                return true;
            }
        }

        return false;
    }
}
