<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit;

use IntrAgile\HungarianValidation\InvalidIdentifierException;
use IntrAgile\HungarianValidation\PostalCode;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Ranges follow the Magyar Posta postal code list (posta.hu, XLSX of
 * 2026-10-12): Budapest uses 1007 (Margitsziget) and 1011-1239, where digits
 * 2-3 are the district, and the rest of the country uses 2000-9999. PO box and
 * organisation codes such as 1410 or 1525 are missing from that list but are
 * accepted, because only the structure is validated.
 */
final class PostalCodeTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function validProvider(): iterable
    {
        yield 'Margitsziget' => ['1007'];
        yield 'first district' => ['1011'];
        yield 'last district code' => ['1239'];
        yield 'PO box code' => ['1410'];
        yield 'first countryside code' => ['2000'];
        yield 'high countryside code' => ['9985'];
    }

    #[DataProvider('validProvider')]
    public function test_valid(string $value): void
    {
        self::assertTrue(PostalCode::isValid($value));
        self::assertNull(PostalCode::validate($value));
        self::assertSame($value, PostalCode::parse($value)->value());
    }

    /**
     * @return iterable<string, array{string, Violation}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'three digits' => ['999', Violation::Format];
        yield 'five digits' => ['10000', Violation::Format];
        yield 'letters' => ['H1052', Violation::Format];
        yield 'full-width digits' => ["\u{FF11}\u{FF10}\u{FF15}\u{FF12}", Violation::Format];
        yield 'empty' => ['', Violation::Format];
        yield 'leading zero' => ['0999', Violation::Range];
        yield 'lower bound' => ['1000', Violation::Range];
        yield 'below Margitsziget' => ['1006', Violation::Range];
        yield 'above Margitsziget' => ['1008', Violation::Range];
        yield 'before first district' => ['1010', Violation::Range];
    }

    #[DataProvider('invalidProvider')]
    public function test_invalid(string $value, Violation $expected): void
    {
        self::assertSame($expected, PostalCode::validate($value));
        self::assertFalse(PostalCode::isValid($value));
        self::assertNull(PostalCode::tryParse($value));
    }

    /**
     * @return iterable<string, array{string, ?int, bool}>
     */
    public static function districtProvider(): iterable
    {
        yield 'district V' => ['1052', 5, true];
        yield 'district I' => ['1011', 1, true];
        yield 'district XXIII' => ['1239', 23, true];
        yield 'Margitsziget belongs to district XIII' => ['1007', 13, true];
        yield 'PO box has no district' => ['1410', null, true];
        yield 'first non-district code' => ['1240', null, true];
        yield 'Szeged' => ['6720', null, false];
    }

    #[DataProvider('districtProvider')]
    public function test_budapest_district(string $value, ?int $district, bool $budapest): void
    {
        $code = PostalCode::parse($value);

        self::assertSame($district, $code->budapestDistrict());
        self::assertSame($budapest, $code->isBudapest());
    }

    public function test_normalises_separators(): void
    {
        self::assertSame('1052', PostalCode::parse(' 1052 ')->value());
        self::assertSame('1052', PostalCode::parse("1\u{00A0}052")->value());
    }

    public function test_parse_throws(): void
    {
        try {
            PostalCode::parse('1000');
            self::fail('Expected InvalidIdentifierException.');
        } catch (InvalidIdentifierException $e) {
            self::assertSame(Violation::Range, $e->violation);
            self::assertSame('postal code', $e->identifierType);
            self::assertSame('Invalid Hungarian postal code: value out of range.', $e->getMessage());
        }
    }

    public function test_equals_and_string(): void
    {
        $code = PostalCode::parse('1052');

        self::assertSame('1052', (string) $code);
        self::assertSame('1052', $code->format());
        self::assertTrue($code->equals(PostalCode::parse('1052')));
        self::assertFalse($code->equals(PostalCode::parse('6720')));
    }
}
