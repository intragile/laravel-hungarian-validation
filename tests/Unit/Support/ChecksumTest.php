<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit\Support;

use IntrAgile\HungarianValidation\Support\Checksum;
use PHPUnit\Framework\TestCase;

/**
 * Reference values come from the MNB document "IBAN ellenőrző szám számítása"
 * (https://www.mnb.hu/letoltes/iban-ellenorzoszam-hu.pdf) and the GIRO rule of
 * 35/2017. (XII. 14.) MNB rendelet, annex 1, point 1.
 */
final class ChecksumTest extends TestCase
{
    public function test_weighted_sum_repeats_weights(): void
    {
        // 1*9 + 2*7 + 3*3 + 4*1 + 5*9 = 81
        self::assertSame(81, Checksum::weighted('12345', [9, 7, 3, 1]));
    }

    public function test_giro_mnb_examples(): void
    {
        self::assertTrue(Checksum::giroGroupValid('11773016'));
        self::assertTrue(Checksum::giroGroupValid('11111018'));
        self::assertTrue(Checksum::giroGroupValid('0000000000000000'));
        self::assertTrue(Checksum::giroGroupValid('2142148011110000'));
        self::assertFalse(Checksum::giroGroupValid('11773017'));
    }

    public function test_mod97_iban_examples(): void
    {
        // HU42117730161111101800000000 rearranged: BBAN + "HU" + check digits.
        self::assertSame(1, Checksum::mod97('117730161111101800000000HU42'));
        self::assertSame(1, Checksum::mod97('370400440532013000DE89'));
        self::assertNotSame(1, Checksum::mod97('117730161111101800000000HU43'));
    }
}
