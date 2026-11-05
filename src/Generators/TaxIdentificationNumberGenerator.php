<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Generators;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntrAgile\HungarianValidation\Support\Checksum;
use IntrAgile\HungarianValidation\TaxIdentificationNumber;
use InvalidArgumentException;
use Random\Randomizer;

/**
 * Generates random tax identification numbers with a correct check digit.
 */
final class TaxIdentificationNumberGenerator
{
    /**
     * The largest day count that fits in five digits.
     */
    private const int MAX_DAYS = 99999;

    public function __construct(private Randomizer $random = new Randomizer) {}

    /**
     * Generates a number embedding the given birth date, or a random date
     * between 1867-01-01 and today when none is given.
     *
     * @throws InvalidArgumentException When the birth date is before 1867-01-01 or after today.
     */
    public function generate(?DateTimeInterface $birthDate = null): TaxIdentificationNumber
    {
        $today = TaxIdentificationNumber::daysSinceEpoch(
            new DateTimeImmutable(TaxIdentificationNumber::today(), new DateTimeZone('UTC')),
        );
        $latest = min($today, self::MAX_DAYS);

        if ($birthDate === null) {
            $days = $this->random->getInt(0, $latest);
        } else {
            $days = TaxIdentificationNumber::daysSinceEpoch($birthDate);

            if ($days < 0 || $days > $latest) {
                throw new InvalidArgumentException('The birth date must be between 1867-01-01 and today.');
            }
        }

        $prefix = '8'.str_pad((string) $days, 5, '0', STR_PAD_LEFT);
        $serial = $this->random->getInt(0, 999);
        $remainder = $this->remainder($prefix, $serial);

        // Stepping the serial (weights 7, 8, 9) by one changes the weighted sum by
        // +9 without a carry, 8 - 81 = -73 with one carry, 7 - 72 - 81 = -146 with
        // two and -63 - 72 - 81 = -216 when 999 wraps to 000. These are 9, 4, 8
        // and 4 mod 11, never 0, so one step always leaves remainder 10.
        if ($remainder === 10) {
            $serial = ($serial + 1) % 1000;
            $remainder = $this->remainder($prefix, $serial);
        }

        return TaxIdentificationNumber::parse($prefix.str_pad((string) $serial, 3, '0', STR_PAD_LEFT).$remainder);
    }

    private function remainder(string $prefix, int $serial): int
    {
        $base = $prefix.str_pad((string) $serial, 3, '0', STR_PAD_LEFT);

        return Checksum::weighted($base, TaxIdentificationNumber::WEIGHTS) % 11;
    }
}
