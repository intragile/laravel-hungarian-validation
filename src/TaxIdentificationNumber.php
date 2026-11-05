<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntrAgile\HungarianValidation\Support\Checksum;
use IntrAgile\HungarianValidation\Support\Normalizer;

/**
 * Hungarian personal tax identification number (adóazonosító jel).
 *
 * Ten digits defined by 1996. évi XX. törvény, annex 1: the 1st digit is 8,
 * digits 2 to 6 are the days elapsed since 1867-01-01 (day 0), digits 7 to 9
 * are a serial distinguishing people born on the same day, and the 10th digit
 * is sum(digit_i * i, i = 1..9) mod 11. A remainder of 10 is never issued.
 */
final readonly class TaxIdentificationNumber implements Identifier
{
    /**
     * Check digit weights for digits 1 to 9.
     *
     * @internal
     *
     * @var list<int>
     */
    public const array WEIGHTS = [1, 2, 3, 4, 5, 6, 7, 8, 9];

    private const string TYPE = 'tax identification number';

    private const string EPOCH = '1867-01-01';

    /**
     * The zone that decides what "today" is when rejecting future birth dates.
     */
    private const string LOCAL_ZONE = 'Europe/Budapest';

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

    /**
     * Returns the first rule the value breaks, or null when it is valid.
     *
     * When a birth date is given, only its calendar date in its own time zone is
     * compared with the embedded date; the time of day is ignored.
     */
    public static function validate(string $value, ?DateTimeInterface $birthDate = null): ?Violation
    {
        $digits = Normalizer::compact($value);

        if (preg_match('/^8\d{9}\z/', $digits) !== 1) {
            return Violation::Format;
        }

        $remainder = Checksum::weighted(substr($digits, 0, 9), self::WEIGHTS) % 11;

        if ($remainder === 10 || $remainder !== (int) $digits[9]) {
            return Violation::Checksum;
        }

        // ISO dates compare correctly as strings.
        $embedded = self::dateFromDays((int) substr($digits, 1, 5))->format('Y-m-d');

        if ($embedded > self::today()) {
            return Violation::BirthDate;
        }

        if ($birthDate !== null && $birthDate->format('Y-m-d') !== $embedded) {
            return Violation::BirthDateMismatch;
        }

        return null;
    }

    /**
     * Days elapsed between 1867-01-01 and the calendar date of the given moment
     * in its own time zone.
     *
     * @internal
     */
    public static function daysSinceEpoch(DateTimeInterface $date): int
    {
        $epoch = new DateTimeImmutable(self::EPOCH, new DateTimeZone('UTC'));
        $midnight = $epoch->setDate((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));

        return intdiv($midnight->getTimestamp() - $epoch->getTimestamp(), 86400);
    }

    /**
     * Today's date in Hungary, the latest birth date a number may embed.
     *
     * @internal
     */
    public static function today(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone(self::LOCAL_ZONE)))->format('Y-m-d');
    }

    public function value(): string
    {
        return $this->value;
    }

    /**
     * The number is written without grouping in practice.
     */
    public function format(): string
    {
        return $this->value;
    }

    public function equals(Identifier $other): bool
    {
        return $other instanceof self && $other->value() === $this->value;
    }

    /**
     * The embedded birth date at 00:00 UTC.
     */
    public function birthDate(): DateTimeImmutable
    {
        return self::dateFromDays((int) substr($this->value, 1, 5));
    }

    /**
     * Digits 7 to 9.
     */
    public function serial(): string
    {
        return substr($this->value, 6, 3);
    }

    /**
     * Whether the calendar date of the given moment, in its own time zone,
     * equals the embedded birth date.
     */
    public function matchesBirthDate(DateTimeInterface $date): bool
    {
        return $date->format('Y-m-d') === $this->birthDate()->format('Y-m-d');
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * setDate() rolls day overflow into months and years.
     */
    private static function dateFromDays(int $days): DateTimeImmutable
    {
        return (new DateTimeImmutable(self::EPOCH, new DateTimeZone('UTC')))->setDate(1867, 1, 1 + $days);
    }
}
