<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Support;

/**
 * @internal
 */
final class Checksum
{
    /**
     * Weights of the GIRO check digit (35/2017. MNB rendelet, annex 1) and of the
     * Hungarian tax number's base number.
     */
    public const array GIRO_WEIGHTS = [9, 7, 3, 1];

    /**
     * Sum of each digit multiplied by its weight; weights repeat cyclically.
     *
     * @param  list<int>  $weights
     */
    public static function weighted(string $digits, array $weights): int
    {
        $sum = 0;
        $count = count($weights);

        foreach (str_split($digits) as $i => $digit) {
            $sum += (int) $digit * $weights[$i % $count];
        }

        return $sum;
    }

    /**
     * Whether a digit group, including its trailing check digit, satisfies the GIRO rule.
     */
    public static function giroGroupValid(string $digits): bool
    {
        return self::weighted($digits, self::GIRO_WEIGHTS) % 10 === 0;
    }

    /**
     * ISO 7064 MOD 97-10 remainder of an alphanumeric string (A = 10 … Z = 35).
     *
     * Computed piecewise so that no arbitrary-precision extension is needed.
     */
    public static function mod97(string $alphanumeric): int
    {
        $remainder = 0;

        foreach (str_split(strtoupper($alphanumeric)) as $char) {
            $chunk = ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
            $remainder = (int) ($remainder.$chunk) % 97;
        }

        return $remainder;
    }
}
