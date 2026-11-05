<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit;

use IntrAgile\HungarianValidation\EuVatNumber;
use IntrAgile\HungarianValidation\InvalidIdentifierException;
use IntrAgile\HungarianValidation\TaxNumber;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\TestCase;

/**
 * A Hungarian EU VAT number is "HU" followed by the 8-digit base number of the
 * tax number (https://nav.gov.hu). The base number check digit rule comes from
 * the Céginformációs Szolgálat
 * (https://ceginformaciosszolgalat.kormany.hu/download/4/27/02000/adoszam_adoazonosito_ellenorzes_2018.pdf).
 *
 * For a VAT group, the EU VAT number is derived from the group identifier, not
 * from the member's tax number: OTP Bank's HU17780010 belongs to the group
 * identifier 17780010-5-44, not to the member 10537914-4-44.
 */
final class EuVatNumberTest extends TestCase
{
    public function test_real_value(): void
    {
        // 9+49+21+8+0+0+3 = 90; (10 - 0) mod 10 = 0
        self::assertTrue(EuVatNumber::isValid('HU17780010'));
        self::assertSame('HU17780010', EuVatNumber::parse('hu 1778 0010')->value());
        self::assertSame('HU17780010', EuVatNumber::parse('HU-17780010')->format());
    }

    public function test_checksum(): void
    {
        self::assertSame(Violation::Checksum, EuVatNumber::validate('HU17780011'));
    }

    public function test_format(): void
    {
        self::assertSame(Violation::Format, EuVatNumber::validate('DE17780010'));
        self::assertSame(Violation::Format, EuVatNumber::validate('HU1778001'));
        self::assertSame(Violation::Format, EuVatNumber::validate('17780010'));
        self::assertSame(Violation::Format, EuVatNumber::validate('HU177800100'));
    }

    public function test_matches_group_not_member(): void
    {
        $vat = EuVatNumber::parse('HU17780010');

        self::assertTrue($vat->matches(TaxNumber::parse('17780010-5-44')));
        self::assertFalse($vat->matches(TaxNumber::parse('10537914-4-44')));
    }

    public function test_from_base_number(): void
    {
        $vat = EuVatNumber::fromBaseNumber('1053 7914');

        self::assertSame('HU10537914', $vat->value());
        self::assertSame('10537914', $vat->baseNumber());
    }

    public function test_parse_throws_with_violation(): void
    {
        try {
            EuVatNumber::parse('HU17780011');
            self::fail('Expected InvalidIdentifierException.');
        } catch (InvalidIdentifierException $e) {
            self::assertSame(Violation::Checksum, $e->violation);
            self::assertSame('EU VAT number', $e->identifierType);
        }

        self::assertNull(EuVatNumber::tryParse('17780010'));
    }

    public function test_equals_and_to_string(): void
    {
        $a = EuVatNumber::parse('hu17780010');

        self::assertTrue($a->equals(EuVatNumber::parse('HU 1778 0010')));
        self::assertFalse($a->equals(EuVatNumber::parse('HU10537914')));
        self::assertFalse($a->equals(TaxNumber::parse('17780010-5-44')));
        self::assertSame('HU17780010', (string) $a);
    }
}
