<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

/**
 * The territorial code: the last two digits of a Hungarian tax number.
 *
 * The list follows NAV's "Tájékoztató az áfaalanynak minősülő adózók adataihoz"
 * (http://nav.gov.hu/nav/adatbazisok/adatbleker/afaalanyok/afaalany_taj.html,
 * archived 2012–2021 at web.archive.org), which has been unchanged since 2008.
 * Codes 22 to 40 repeat the counties of 02 to 20. Codes already issued are not
 * renumbered, so 44 and 51 stay valid after their directorates were merged.
 */
final class TaxRegion
{
    /**
     * Keyed by the numeric value of the code, as PHP turns numeric string keys into integers.
     *
     * @var array<int, non-empty-string>
     */
    private const array NAMES = [
        2 => 'Baranya',
        3 => 'Bács-Kiskun',
        4 => 'Békés',
        5 => 'Borsod-Abaúj-Zemplén',
        6 => 'Csongrád-Csanád',
        7 => 'Fejér',
        8 => 'Győr-Moson-Sopron',
        9 => 'Hajdú-Bihar',
        10 => 'Heves',
        11 => 'Komárom-Esztergom',
        12 => 'Nógrád',
        13 => 'Pest',
        14 => 'Somogy',
        15 => 'Szabolcs-Szatmár-Bereg',
        16 => 'Jász-Nagykun-Szolnok',
        17 => 'Tolna',
        18 => 'Vas',
        19 => 'Veszprém',
        20 => 'Zala',
        22 => 'Baranya',
        23 => 'Bács-Kiskun',
        24 => 'Békés',
        25 => 'Borsod-Abaúj-Zemplén',
        26 => 'Csongrád-Csanád',
        27 => 'Fejér',
        28 => 'Győr-Moson-Sopron',
        29 => 'Hajdú-Bihar',
        30 => 'Heves',
        31 => 'Komárom-Esztergom',
        32 => 'Nógrád',
        33 => 'Pest',
        34 => 'Somogy',
        35 => 'Szabolcs-Szatmár-Bereg',
        36 => 'Jász-Nagykun-Szolnok',
        37 => 'Tolna',
        38 => 'Vas',
        39 => 'Veszprém',
        40 => 'Zala',
        41 => 'Észak-Budapest',
        42 => 'Kelet-Budapest',
        43 => 'Dél-Budapest',
        44 => 'Kiemelt Adó- és Vámigazgatóság',
        51 => 'Kiemelt Adó- és Vámigazgatóság',
    ];

    private function __construct() {}

    public static function isKnown(string $code): bool
    {
        return self::name($code) !== null;
    }

    /**
     * The Hungarian name of the county or tax directorate, or null for an unknown code.
     */
    public static function name(string $code): ?string
    {
        if (preg_match('/^\d{2}\z/', $code) !== 1) {
            return null;
        }

        return self::NAMES[(int) $code] ?? null;
    }

    /**
     * Every known territorial code, in ascending order.
     *
     * @return list<non-empty-string>
     */
    public static function codes(): array
    {
        return array_map(
            static fn (int $code): string => sprintf('%02d', $code),
            array_keys(self::NAMES),
        );
    }
}
