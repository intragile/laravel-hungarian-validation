<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit;

use IntrAgile\HungarianValidation\InvalidIdentifierException;
use IntrAgile\HungarianValidation\TaxNumber;
use IntrAgile\HungarianValidation\TaxRegion;
use IntrAgile\HungarianValidation\VatCode;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\TestCase;

/**
 * The base number check digit rule comes from the Céginformációs Szolgálat
 * (https://ceginformaciosszolgalat.kormany.hu/download/4/27/02000/adoszam_adoazonosito_ellenorzes_2018.pdf):
 * digits 1 to 7 are weighted 9, 7, 3, 1, 9, 7, 3 and the 8th digit is
 * (10 - sum mod 10) mod 10.
 *
 * VAT codes 1 to 5 follow the NAV Online Számla schema (common.xsd, VatCodeType,
 * https://github.com/nav-gov-hu/Online-Invoice). Territorial codes follow NAV's
 * archived taxpayer-data notice
 * (http://nav.gov.hu/nav/adatbazisok/adatbleker/afaalanyok/afaalany_taj.html).
 * Since 2024 NAV also issues 9-prefixed tax numbers to sole proprietors
 * (https://nav.gov.hu), checked with the same algorithm.
 *
 * Real numbers are taken from the companies' published imprints; other
 * fixtures are derived by hand below.
 */
final class TaxNumberTest extends TestCase
{
    public function test_real_tax_numbers_are_valid(): void
    {
        // OTP Bank Nyrt.
        self::assertTrue(TaxNumber::isValid('10537914-4-44'));
        // OTP VAT group identifier: 9+49+21+8+0+0+3 = 90; (10 - 0) mod 10 = 0
        self::assertTrue(TaxNumber::isValid('17780010-5-44'));
        // Magyar Telekom Nyrt.: 9+0+21+7+27+21+24 = 109; (10 - 9) mod 10 = 1
        self::assertTrue(TaxNumber::isValid('10773381-2-44'));
        // MOL Nyrt.: 9+0+18+2+45+49+27 = 150; (10 - 0) mod 10 = 0
        self::assertTrue(TaxNumber::isValid('10625790-4-44'));
    }

    public function test_rejects_wrong_check_digit(): void
    {
        self::assertSame(Violation::Checksum, TaxNumber::validate('10537915-4-44'));
        self::assertSame(Violation::Checksum, TaxNumber::validate('10773380-2-44'));
    }

    /**
     * Regression for packages that compare the check digit with the plain
     * remainder: 1*9 + 0*7 + 5*3 + 3*1 + 7*9 + 9*7 + 1*3 = 156; (10 - 6) mod 10 = 4.
     */
    public function test_formula_is_ten_minus_remainder(): void
    {
        self::assertTrue(TaxNumber::isValidBaseNumber('10537914'));
        self::assertFalse(TaxNumber::isValidBaseNumber('10537916'));
        self::assertFalse(TaxNumber::isValidBaseNumber('1053791'));
        self::assertFalse(TaxNumber::isValidBaseNumber('1053791a'));
    }

    /**
     * Regression for packages that accept any digit as the VAT code.
     */
    public function test_rejects_vat_code_zero_and_six(): void
    {
        self::assertSame(Violation::VatCode, TaxNumber::validate('10537914-0-44'));
        self::assertSame(Violation::VatCode, TaxNumber::validate('10537914-6-44'));
    }

    public function test_rejects_unknown_region(): void
    {
        foreach (['01', '21', '45', '50', '52'] as $region) {
            self::assertSame(Violation::Region, TaxNumber::validate('10537914-4-'.$region), $region);
            self::assertFalse(TaxRegion::isKnown($region));
        }

        foreach (['02', '20', '22', '44', '51'] as $region) {
            self::assertNull(TaxNumber::validate('10537914-4-'.$region), $region);
            self::assertTrue(TaxRegion::isKnown($region));
            self::assertNotNull(TaxRegion::name($region));
        }

        self::assertSame('Pest', TaxRegion::name('13'));
        self::assertNull(TaxRegion::name('21'));
        self::assertFalse(TaxRegion::isKnown('2'));
        self::assertFalse(TaxRegion::isKnown('013'));
        self::assertSame('02', TaxRegion::codes()[0]);
        self::assertCount(43, TaxRegion::codes());
    }

    public function test_accepts_compact_and_spaced_input(): void
    {
        $inputs = [
            '10773381244',
            '10773381 2 44',
            "10773381\u{2013}2\u{2013}44",
            "10773381\u{00A0}2\u{00A0}44",
            '  10773381-2-44  ',
        ];

        foreach ($inputs as $input) {
            self::assertSame('10773381-2-44', TaxNumber::parse($input)->format(), $input);
            self::assertSame('10773381244', TaxNumber::parse($input)->value(), $input);
        }

        self::assertSame(Violation::Format, TaxNumber::validate('1077338124'));
        self::assertSame(Violation::Format, TaxNumber::validate('107733812440'));
        self::assertSame(Violation::Format, TaxNumber::validate('1077338a-2-44'));
        self::assertSame(Violation::Format, TaxNumber::validate(''));
    }

    public function test_parts(): void
    {
        $taxNumber = TaxNumber::parse('10537914-4-44');

        self::assertSame('10537914', $taxNumber->baseNumber());
        self::assertSame(VatCode::GroupMember, $taxNumber->vatCode());
        self::assertSame('44', $taxNumber->regionCode());
        self::assertSame('csoportazonosító', VatCode::Group->label('hu'));
        self::assertSame('VAT group identifier', VatCode::Group->label());
    }

    /**
     * 9*9 + 1*7 + 2*3 + 3*1 + 4*9 + 5*7 + 6*3 = 81+7+6+3+36+35+18 = 186;
     * (10 - 6) mod 10 = 4.
     */
    public function test_nine_prefixed_individual_numbers_follow_same_rule(): void
    {
        self::assertTrue(TaxNumber::isValid('91234564-2-13'));
        self::assertSame(Violation::Checksum, TaxNumber::validate('91234565-2-13'));
    }

    public function test_parse_throws_with_violation(): void
    {
        try {
            TaxNumber::parse('10537915-4-44');
            self::fail('Expected InvalidIdentifierException.');
        } catch (InvalidIdentifierException $e) {
            self::assertSame(Violation::Checksum, $e->violation);
            self::assertSame('tax number', $e->identifierType);
        }
    }

    public function test_try_parse_returns_null(): void
    {
        self::assertNull(TaxNumber::tryParse('10537914-4-21'));
        self::assertNull(TaxNumber::tryParse('not a tax number'));
        self::assertNotNull(TaxNumber::tryParse('10537914-4-44'));
    }

    public function test_eu_vat_number(): void
    {
        self::assertSame('HU17780010', TaxNumber::parse('17780010-5-44')->euVatNumber()->value());
    }

    public function test_equals_and_to_string(): void
    {
        $a = TaxNumber::parse('10773381244');
        $b = TaxNumber::parse('10773381-2-44');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(TaxNumber::parse('10537914-4-44')));
        self::assertSame('10773381-2-44', (string) $a);
    }
}
