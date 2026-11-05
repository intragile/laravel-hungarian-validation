<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit;

use IntrAgile\HungarianValidation\PostalCode;
use PHPUnit\Framework\TestCase;

/**
 * Fixtures were looked up by hand in the KSH Gazetteer of Hungary 2026
 * (hnt_letoltes_2026.xlsx), sheet "Településrészek 2026.01.01." (columns
 * A = Helység KSH kód, B = Helység hivatalos megnevezése, F = Postai
 * irányító-szám) and sheet "Megj. postai ir. számhoz" (A = Helység KSH kód,
 * B = note). Row numbers are the worksheet row numbers.
 */
final class PostalCodeSettlementTest extends TestCase
{
    /**
     * Településrészek row 508: 22327 Badacsonytomaj, Badacsonyiszőlők, 8261.
     * Megj. row 2 (KSH code 22327): "Badacsony városrész postai irányítószáma: 8261."
     */
    public function test_badacsony_code_belongs_to_badacsonytomaj(): void
    {
        self::assertContains('Badacsonytomaj', PostalCode::parse('8261')->settlements());
    }

    /**
     * Településrészek row 5221: 20473 Kaposvár, Toponár, 7461.
     */
    public function test_toponar_code_belongs_to_kaposvar(): void
    {
        self::assertSame(['Kaposvár'], PostalCode::parse('7461')->settlements());
    }

    /**
     * Településrészek rows with 7838 in column F: 1248 Besence, 1928 Cún,
     * 4441 Hirics, 6760 Lúzsok, 7740 Nagycsány, 9132-9133 Páprád, 9506 Piskó,
     * 12975-12978 Vajszló, 13207-13208 Vejti. Sorted alphabetically (á sorts with a).
     */
    public function test_code_shared_by_nine_settlements(): void
    {
        self::assertSame(
            ['Besence', 'Cún', 'Hirics', 'Lúzsok', 'Nagycsány', 'Páprád', 'Piskó', 'Vajszló', 'Vejti'],
            PostalCode::parse('7838')->settlements(),
        );
    }

    /**
     * Településrészek row 13566: 32522 Zalaszentgrót, Csáford külterület, "8790/8795".
     */
    public function test_cell_with_two_codes(): void
    {
        self::assertSame(['Zalaszentgrót'], PostalCode::parse('8790')->settlements());
        self::assertSame(['Zalaszentgrót'], PostalCode::parse('8795')->settlements());
    }

    /**
     * Neither code appears in Településrészek. Megj. row 20 (KSH code 5801,
     * Gávavencsellő): "...postai irányítószáma 4472." without a colon; Megj.
     * row 36 (KSH code 23065, Orosháza): "...postai irányítószáma:5905." without a space.
     */
    public function test_codes_only_named_in_notes(): void
    {
        self::assertSame(['Gávavencsellő'], PostalCode::parse('4472')->settlements());
        self::assertSame(['Orosháza'], PostalCode::parse('5905')->settlements());
    }

    /**
     * Budapest codes are answered without the data file.
     */
    public function test_budapest(): void
    {
        self::assertSame(['Budapest'], PostalCode::parse('1052')->settlements());
        self::assertSame(['Budapest'], PostalCode::parse('1410')->settlements());
        self::assertTrue(PostalCode::parse('1052')->matchesSettlement('Budapest'));
        self::assertTrue(PostalCode::parse('1007')->matchesSettlement(' budapest '));
        self::assertFalse(PostalCode::parse('1052')->matchesSettlement('Szeged'));
    }

    /**
     * 6720 (a Szeged delivery district code) and 9999 appear in neither sheet:
     * Megj. row 44 says the Szeged district codes are in a separate street list.
     */
    public function test_unknown_code(): void
    {
        foreach (['6720', '9999'] as $code) {
            self::assertSame([], PostalCode::parse($code)->settlements(), $code);
            self::assertNull(PostalCode::parse($code)->matchesSettlement('Szeged'), $code);
        }
    }

    /**
     * Településrészek row 11027: 33367 Szeged, Központi belterület, 6700;
     * row 11243: 15440 Szentendre, Központi belterület, 2000.
     */
    public function test_match_tolerates_case_and_whitespace(): void
    {
        $code = PostalCode::parse('6700');

        self::assertTrue($code->matchesSettlement('Szeged'));
        self::assertTrue($code->matchesSettlement('  SZEGED '));
        self::assertTrue(PostalCode::parse('7838')->matchesSettlement("lúzsok\t"));
        self::assertTrue(PostalCode::parse('7838')->matchesSettlement('LÚZSOK'));
        self::assertTrue(PostalCode::parse('8795')->matchesSettlement('zalaszentgrót'));
        self::assertTrue(PostalCode::parse('2000')->matchesSettlement("\u{00A0}Szentendre\u{00A0}\n"));
        self::assertFalse(PostalCode::parse('2000')->matchesSettlement('Szent endre'));
    }

    /**
     * Településrészek rows 5221 (Kaposvár, 7461) and 11027 (Szeged, 6700).
     */
    public function test_mismatch(): void
    {
        self::assertFalse(PostalCode::parse('7461')->matchesSettlement('Pécs'));
        self::assertFalse(PostalCode::parse('6700')->matchesSettlement(''));
        self::assertFalse(PostalCode::parse('6700')->matchesSettlement('Szeg'));
        self::assertFalse(PostalCode::parse('6700')->matchesSettlement('Szeged.*'));
    }

    public function test_data_file_covers_the_gazetteer(): void
    {
        $data = self::data();

        self::assertGreaterThanOrEqual(2800, count($data));

        // PHP stores the numeric string keys as integers.
        foreach ($data as $code => $names) {
            $code = (string) $code;

            self::assertTrue(PostalCode::isValid($code), $code);
            self::assertFalse(PostalCode::parse($code)->isBudapest(), $code);
            self::assertNotSame([], $names, $code);
            self::assertSame(array_values(array_unique($names)), $names, $code);
        }

        self::assertSame(array_map('strval', array_keys($data)), self::sortedKeys($data));
    }

    /**
     * @return array<int|string, list<string>>
     */
    private static function data(): array
    {
        /** @var array<int|string, list<string>> $data */
        $data = require dirname(__DIR__, 2).'/resources/postal-codes.php';

        return $data;
    }

    /**
     * @param  array<int|string, list<string>>  $data
     * @return list<string>
     */
    private static function sortedKeys(array $data): array
    {
        $keys = array_map('strval', array_keys($data));
        sort($keys, SORT_STRING);

        return $keys;
    }
}
