<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit;

use IntrAgile\HungarianValidation\InvalidIdentifierException;
use IntrAgile\HungarianValidation\TajNumber;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\TestCase;

/**
 * The check digit rule comes from 1996. évi XX. törvény, annex 2
 * (https://net.jogtar.hu/jogszabaly?docid=99600020.tv): digits 1 to 8 are
 * weighted 3 at odd positions and 7 at even positions, and the 9th digit is the
 * weighted sum mod 10.
 *
 * Fixtures are derived by hand below.
 */
final class TajNumberTest extends TestCase
{
    public function test_valid(): void
    {
        // 1*3 + 2*7 + 3*3 + 4*7 + 5*3 + 6*7 + 7*3 + 8*7 = 188; 188 mod 10 = 8
        self::assertTrue(TajNumber::isValid('123456788'));
        $taj = TajNumber::parse('123456788');
        self::assertSame('123456788', $taj->value());
        self::assertSame('123 456 788', $taj->format());

        // 4 * (1*3) + 4 * (1*7) = 40; 40 mod 10 = 0
        self::assertTrue(TajNumber::isValid('111111110'));

        // Only the 8th digit is non-zero: 1*7 = 7; 7 mod 10 = 7
        self::assertTrue(TajNumber::isValid('000000017'));
    }

    public function test_wrong_check_digit(): void
    {
        // 188 mod 10 = 8, not 9
        self::assertSame(Violation::Checksum, TajNumber::validate('123456789'));
        self::assertNull(TajNumber::tryParse('123456789'));

        try {
            TajNumber::parse('123456789');
            self::fail('Expected InvalidIdentifierException.');
        } catch (InvalidIdentifierException $e) {
            self::assertSame(Violation::Checksum, $e->violation);
            self::assertSame('TAJ number', $e->identifierType);
        }
    }

    public function test_format_tolerance(): void
    {
        foreach (['123-456-788', '123 456 788'] as $input) {
            $taj = TajNumber::parse($input);
            self::assertSame('123456788', $taj->value());
            self::assertSame('123 456 788', $taj->format());
        }

        self::assertSame(Violation::Format, TajNumber::validate('12345678'));
        self::assertSame(Violation::Format, TajNumber::validate('1234567880'));
    }

    public function test_non_digit_is_format(): void
    {
        self::assertSame(Violation::Format, TajNumber::validate('12345678a'));
        self::assertSame(Violation::Format, TajNumber::validate(''));
        self::assertSame(Violation::Format, TajNumber::validate("123456788\n0"));
    }

    public function test_equals_and_to_string(): void
    {
        $a = TajNumber::parse('123-456-788');
        $b = TajNumber::parse('123 456 788');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(TajNumber::parse('111111110')));
        self::assertSame('123 456 788', (string) $a);
    }
}
