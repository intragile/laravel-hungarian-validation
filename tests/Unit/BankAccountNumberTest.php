<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit;

use IntrAgile\HungarianValidation\BankAccountNumber;
use IntrAgile\HungarianValidation\InvalidIdentifierException;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\TestCase;

/**
 * The check digit rule comes from 35/2017. (XII. 14.) MNB rendelet, annex 1:
 * digits are weighted 9, 7, 3, 1 cyclically and each group, including its
 * trailing check digit, must sum to a multiple of 10. The first group is digits
 * 1 to 8; the second is the rest of the number (8 or 16 digits) taken as one group.
 *
 * Reference values come from the MNB document "IBAN ellenőrző szám számítása"
 * (https://www.mnb.hu/letoltes/iban-ellenorzoszam-hu.pdf) and from the list of
 * NAV collection accounts published on nav.gov.hu.
 */
final class BankAccountNumberTest extends TestCase
{
    public function test_mnb_examples(): void
    {
        self::assertTrue(BankAccountNumber::isValid('11773016-11111018-00000000'));
        self::assertTrue(BankAccountNumber::isValid('10700079-21421480-11110000'));
    }

    public function test_real_nav_accounts(): void
    {
        self::assertTrue(BankAccountNumber::isValid('10032000-01076019-00000000'));
        self::assertTrue(BankAccountNumber::isValid('10032000-06056353-00000000'));
    }

    public function test_16_digit_form(): void
    {
        self::assertTrue(BankAccountNumber::isValid('11773016-11111018'));

        $account = BankAccountNumber::parse('11773016-11111018');
        self::assertSame(16, $account->length());
        self::assertSame('1177301611111018', $account->value());
        self::assertSame('11773016-11111018', $account->format());

        $long = BankAccountNumber::parse('11773016 11111018 00000000');
        self::assertSame(24, $long->length());
        self::assertSame('117730161111101800000000', $long->value());
        self::assertSame('11773016-11111018-00000000', $long->format());
    }

    /**
     * Digits 9 to 24 form a single group, so digit 16 is not a check digit:
     * 0000000100000009 gives 1*1 + 9*1 = 10, and 10 mod 10 = 0. Checking the
     * second and third blocks separately would reject this number.
     */
    public function test_digit_16_is_free_in_24_digit_numbers(): void
    {
        self::assertTrue(BankAccountNumber::isValid('11773016-00000001-00000009'));
    }

    public function test_wrong_check_digit(): void
    {
        // 1*9 + 1*7 + 7*3 + 7*1 + 3*9 + 0*7 + 1*3 + 7*1 = 81; 81 mod 10 = 1
        self::assertSame(Violation::Checksum, BankAccountNumber::validate('11773017-11111018'));

        // 1*9 + 1*7 + 1*3 + 1*1 + 1*9 + 0*7 + 1*3 + 9*1 = 41; 41 mod 10 = 1
        self::assertSame(Violation::Checksum, BankAccountNumber::validate('11773016-11111019-00000000'));
    }

    public function test_length(): void
    {
        self::assertSame(Violation::Length, BankAccountNumber::validate('12345678'));
        self::assertSame(Violation::Length, BankAccountNumber::validate('11773016111110180'));
        self::assertSame(Violation::Length, BankAccountNumber::validate('11773016111110180000000000000000'));
        self::assertSame(Violation::Format, BankAccountNumber::validate('1177301A-11111018'));
        self::assertSame(Violation::Format, BankAccountNumber::validate(''));
    }

    public function test_parts(): void
    {
        $account = BankAccountNumber::parse('11773016-11111018');
        self::assertSame('117', $account->bankCode());
        self::assertSame('7301', $account->branchCode());

        self::assertNull(BankAccountNumber::tryParse('11773017-11111018'));

        try {
            BankAccountNumber::parse('11773017-11111018');
            self::fail('Expected InvalidIdentifierException.');
        } catch (InvalidIdentifierException $e) {
            self::assertSame(Violation::Checksum, $e->violation);
            self::assertSame('bank account number', $e->identifierType);
        }

        self::assertTrue($account->equals(BankAccountNumber::parse('1177301611111018')));
        self::assertFalse($account->equals(BankAccountNumber::parse('11773016-11111018-00000000')));
        self::assertSame('11773016-11111018', (string) $account);
    }
}
