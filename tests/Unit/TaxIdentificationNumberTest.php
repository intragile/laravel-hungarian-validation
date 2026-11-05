<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use IntrAgile\HungarianValidation\InvalidIdentifierException;
use IntrAgile\HungarianValidation\TaxIdentificationNumber;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The rules come from 1996. évi XX. törvény, annex 1
 * (https://net.jogtar.hu/jogszabaly?docid=99600020.tv): ten digits, the first
 * is 8, digits 2 to 6 are the days elapsed since 1867-01-01 (which is day 0),
 * digits 7 to 9 are a serial, and the 10th digit is
 * sum(digit_i * i, i = 1..9) mod 11. A remainder of 10 is never issued.
 *
 * Fixtures are derived by hand below.
 *
 * 41272 days = 1980-01-01: 1867 to 1979 is 113 years; leap years 1868 to 1976
 * are (1976 - 1868) / 4 + 1 = 28, minus 1900, so 27; 113 * 365 + 27 = 41272.
 *
 * 8412721233: 8*1 + 4*2 + 1*3 + 2*4 + 7*5 + 2*6 + 1*7 + 2*8 + 3*9
 *   = 8 + 8 + 3 + 8 + 35 + 12 + 7 + 16 + 27 = 124; 124 = 11*11 + 3, check digit 3.
 * 8412721241: the 9th digit is 4 instead of 3, so the sum grows by 9 to 133;
 *   133 = 12*11 + 1, check digit 1, serial 124.
 * 841272125x: the 9th digit is 5, so the sum is 124 + 2*9 = 142; 142 = 12*11 + 10.
 * 8999990001: 8 + 9 * (2 + 3 + 4 + 5 + 6) = 188; 188 = 17*11 + 1, check digit 1.
 *   Day 99999: 1867 to 2139 is 273 years with 68 - 2 (1900, 2100) = 66 leap
 *   years, 273 * 365 + 66 = 99711 days; the remaining 288 days fall in leap
 *   year 2140 (January to September is 274 days), giving 2140-10-15.
 */
final class TaxIdentificationNumberTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validNumbers(): iterable
    {
        yield 'serial 123' => ['8412721233', '123'];
        yield 'serial 124' => ['8412721241', '124'];
    }

    #[DataProvider('validNumbers')]
    public function test_valid(string $value, string $serial): void
    {
        self::assertTrue(TaxIdentificationNumber::isValid($value));
        self::assertNull(TaxIdentificationNumber::validate($value));

        $number = TaxIdentificationNumber::parse($value);
        self::assertSame($value, $number->value());
        self::assertSame($value, $number->format());
        self::assertSame('1980-01-01', $number->birthDate()->format('Y-m-d'));
        self::assertSame('00:00:00 UTC', $number->birthDate()->format('H:i:s e'));
        self::assertSame($serial, $number->serial());
    }

    public function test_wrong_check_digit(): void
    {
        // The correct check digit is 3.
        self::assertSame(Violation::Checksum, TaxIdentificationNumber::validate('8412721234'));

        // The checksum rule is reported before a birth date mismatch.
        self::assertSame(
            Violation::Checksum,
            TaxIdentificationNumber::validate('8412721234', new DateTimeImmutable('2000-06-15')),
        );
    }

    public function test_remainder_ten_is_invalid(): void
    {
        for ($digit = 0; $digit <= 9; $digit++) {
            self::assertSame(Violation::Checksum, TaxIdentificationNumber::validate('841272125'.$digit));
        }
    }

    public function test_must_start_with_8(): void
    {
        self::assertSame(Violation::Format, TaxIdentificationNumber::validate('7412721233'));
        self::assertSame(Violation::Format, TaxIdentificationNumber::validate('841272123'));
        self::assertSame(Violation::Format, TaxIdentificationNumber::validate('84127212330'));
        self::assertSame(Violation::Format, TaxIdentificationNumber::validate('841272123a'));
        self::assertSame(Violation::Format, TaxIdentificationNumber::validate(''));
    }

    public function test_future_birth_date(): void
    {
        // Day 99999 is 2140-10-15 and the check digit is correct.
        self::assertSame(Violation::BirthDate, TaxIdentificationNumber::validate('8999990001'));
    }

    public function test_birth_date_match_ignores_time_and_timezone(): void
    {
        $number = TaxIdentificationNumber::parse('8412721233');

        // 1980-01-01 10:30 UTC, but the calendar date in its own zone is what counts.
        $auckland = new DateTimeImmutable('1980-01-01 23:30', new DateTimeZone('Pacific/Auckland'));
        self::assertNull(TaxIdentificationNumber::validate('8412721233', $auckland));
        self::assertTrue($number->matchesBirthDate($auckland));

        $other = new DateTimeImmutable('1980-01-02');
        self::assertSame(Violation::BirthDateMismatch, TaxIdentificationNumber::validate('8412721233', $other));
        self::assertFalse($number->matchesBirthDate($other));
    }

    public function test_normalizes_separators(): void
    {
        self::assertSame('8412721233', TaxIdentificationNumber::parse('84127 21233')->value());
        self::assertSame('8412721233', TaxIdentificationNumber::parse('8412-721-233')->value());
    }

    public function test_parse_throws_and_try_parse_returns_null(): void
    {
        self::assertNull(TaxIdentificationNumber::tryParse('8412721234'));
        self::assertNotNull(TaxIdentificationNumber::tryParse('8412721233'));

        try {
            TaxIdentificationNumber::parse('8412721234');
            self::fail('Expected InvalidIdentifierException.');
        } catch (InvalidIdentifierException $e) {
            self::assertSame(Violation::Checksum, $e->violation);
            self::assertSame('tax identification number', $e->identifierType);
        }
    }

    public function test_equals_and_string_cast(): void
    {
        $a = TaxIdentificationNumber::parse('8412721233');
        $b = TaxIdentificationNumber::parse('8412-721-233');
        $c = TaxIdentificationNumber::parse('8412721241');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
        self::assertSame('8412721233', (string) $a);
    }
}
