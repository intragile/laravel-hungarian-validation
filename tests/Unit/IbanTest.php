<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit;

use IntrAgile\HungarianValidation\BankAccountNumber;
use IntrAgile\HungarianValidation\Iban;
use IntrAgile\HungarianValidation\InvalidIdentifierException;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\TestCase;

/**
 * A Hungarian IBAN is "HU", two ISO 7064 MOD 97-10 check digits and the 24-digit
 * domestic account number, whose GIRO check digits (35/2017. (XII. 14.) MNB
 * rendelet, annex 1) are verified as well.
 *
 * Reference values come from the MNB document "IBAN ellenőrző szám számítása"
 * (https://www.mnb.hu/letoltes/iban-ellenorzoszam-hu.pdf) and from the list of
 * NAV collection accounts published on nav.gov.hu.
 */
final class IbanTest extends TestCase
{
    public function test_valid(): void
    {
        self::assertTrue(Iban::isValid('HU42117730161111101800000000'));
        self::assertTrue(Iban::isValid('HU93107000792142148011110000'));
        self::assertTrue(Iban::isValid('HU57100320000107601900000000'));
        self::assertTrue(Iban::isValid('HU16100320000605635300000000'));
        self::assertTrue(Iban::isValid('HU68100320000107686800000000'));
    }

    public function test_lowercase_and_spaces(): void
    {
        $iban = Iban::parse('hu42 1177 3016 1111 1018 0000 0000');

        self::assertSame('HU42 1177 3016 1111 1018 0000 0000', $iban->format());
        self::assertSame('HU42117730161111101800000000', $iban->value());
        self::assertSame('HU42 1177 3016 1111 1018 0000 0000', (string) $iban);
        self::assertSame(24, $iban->bankAccount()->length());
    }

    public function test_wrong_mod97(): void
    {
        self::assertSame(Violation::Checksum, Iban::validate('HU43117730161111101800000000'));
        self::assertNull(Iban::tryParse('HU43117730161111101800000000'));

        try {
            Iban::parse('HU43117730161111101800000000');
            self::fail('Expected InvalidIdentifierException.');
        } catch (InvalidIdentifierException $e) {
            self::assertSame(Violation::Checksum, $e->violation);
            self::assertSame('IBAN', $e->identifierType);
        }
    }

    /**
     * The check digits of HU86117730161111101900000000 are correct, but the
     * embedded account number is not.
     *
     * Rearranged (BBAN, then "HU" as 1730, then "00"): 117730161111101900000000173000.
     * Its remainder, computed piecewise:
     * - 117730161 mod 97 = 0 (97 * 1213713 = 117730161)
     * - 1111019 mod 97 = 78 (97 * 11453 = 1110941)
     * - 780000000 mod 97 = 11 (97 * 8041237 = 779999989)
     * - 110173000 mod 97 = 12 (97 * 1135804 = 110172988)
     * The check digits are 98 - 12 = 86; with them in place, 110173086 mod 97 = 1.
     *
     * The second account block 11111019 fails GIRO:
     * 1*9 + 1*7 + 1*3 + 1*1 + 1*9 + 0*7 + 1*3 + 9*1 = 41; 41 mod 10 = 1.
     */
    public function test_valid_mod97_but_bad_giro(): void
    {
        self::assertSame(Violation::Checksum, Iban::validate('HU86117730161111101900000000'));
    }

    public function test_foreign_iban(): void
    {
        self::assertSame(Violation::Format, Iban::validate('DE89370400440532013000'));
        self::assertSame(Violation::Format, Iban::validate('HU4211773016111110180000000'));
        self::assertSame(Violation::Format, Iban::validate('HU42A17730161111101800000000'));
        self::assertSame(Violation::Format, Iban::validate(''));
    }

    public function test_roundtrip(): void
    {
        $account = BankAccountNumber::parse('11773016-11111018');

        self::assertSame('HU42117730161111101800000000', $account->toIban()->value());
        self::assertTrue(Iban::fromBankAccount($account)->equals(Iban::parse('HU42117730161111101800000000')));
        self::assertSame('117730161111101800000000', Iban::parse('HU42117730161111101800000000')->bankAccount()->value());
        self::assertSame('HU93107000792142148011110000', BankAccountNumber::parse('10700079-21421480-11110000')->toIban()->value());
    }

    /**
     * ISO 13616 allows check digits 02 to 98 only. 99 and 02 leave the same MOD 97
     * remainder, so HU99 + BBAN passes the remainder test whenever HU02 + BBAN does.
     */
    public function test_rejects_check_digits_outside_02_to_98(): void
    {
        self::assertSame(Violation::Checksum, Iban::validate('HU99709310841441804066458214'));
        self::assertSame(Violation::Checksum, Iban::validate('HU00117730161111101800000000'));
        self::assertSame(Violation::Checksum, Iban::validate('HU01117730161111101800000000'));
    }
}
