<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use IntrAgile\HungarianValidation\Laravel\Rules\HungarianPostalCode;
use IntrAgile\HungarianValidation\Laravel\StringRules;
use IntrAgile\HungarianValidation\Violation;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Fixtures from the KSH Gazetteer of Hungary 2026 (hnt_letoltes_2026.xlsx),
 * sheet "Településrészek 2026.01.01.", looked up by hand:
 * row 5221 (20473 Kaposvár, Toponár, 7461), row 11027 (33367 Szeged,
 * Központi belterület, 6700). 6720 appears in no sheet of the file: the Szeged
 * delivery district codes are listed separately ("Megj. postai ir. számhoz" row 44).
 */
final class PostalCodeCityTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, mixed, ?Violation}>
     */
    public static function cases(): iterable
    {
        yield 'unknown code, any city' => ['6720', 'Szeged', null];
        yield 'unknown code, other city' => ['6720', 'Pécs', null];
        yield 'matching city' => ['7461', 'Kaposvár', null];
        yield 'matching city, other case' => ['6700', ' szeged ', null];
        yield 'mismatching city' => ['7461', 'Pécs', Violation::SettlementMismatch];
        yield 'Budapest' => ['1052', 'Budapest', null];
        yield 'Budapest mismatch' => ['1052', 'Szeged', Violation::SettlementMismatch];
        yield 'integer code' => [7461, 'Pécs', Violation::SettlementMismatch];
        yield 'structure first' => ['1008', 'Budapest', Violation::Range];
        yield 'missing city is skipped' => ['7461', null, null];
        yield 'empty city is skipped' => ['7461', '  ', null];
        yield 'non-string city is skipped' => ['7461', ['Pécs'], null];
    }

    #[DataProvider('cases')]
    public function test_settlement_field(mixed $code, mixed $city, ?Violation $violation): void
    {
        $expected = $violation === null ? [] : ['zip' => $this->expected(HungarianPostalCode::messageKey($violation), 'zip')];

        foreach ([new HungarianPostalCode(settlementField: 'address.city'), 'hu_postal_code:settlement=address.city'] as $rule) {
            self::assertSame($expected, $this->errors(['zip' => $code, 'address' => ['city' => $city]], ['zip' => [$rule]]));
        }
    }

    public function test_absent_settlement_field_is_skipped(): void
    {
        self::assertSame([], $this->errors(['zip' => '7461'], ['zip' => 'hu_postal_code:settlement=city']));
    }

    public function test_combined_with_budapest_only(): void
    {
        $notAllowed = $this->expected(HungarianPostalCode::messageKey(Violation::NotAllowed), 'zip');
        $mismatch = $this->expected(HungarianPostalCode::messageKey(Violation::SettlementMismatch), 'zip');

        foreach ([new HungarianPostalCode(true, 'city'), 'hu_postal_code:budapest,settlement=city'] as $rule) {
            self::assertSame([], $this->errors(['zip' => '1052', 'city' => 'budapest'], ['zip' => [$rule]]));
            self::assertSame(['zip' => $mismatch], $this->errors(['zip' => '1052', 'city' => 'Győr'], ['zip' => [$rule]]));
            self::assertSame(['zip' => $notAllowed], $this->errors(['zip' => '7461', 'city' => 'Kaposvár'], ['zip' => [$rule]]));
        }
    }

    public function test_wildcard_settlement_field(): void
    {
        $data = ['addresses' => [
            ['zip' => '7461', 'city' => 'Kaposvár'],
            ['zip' => '7461', 'city' => 'Pécs'],
        ]];
        $mismatch = $this->expected(HungarianPostalCode::messageKey(Violation::SettlementMismatch), 'addresses.1.zip');

        foreach ([new HungarianPostalCode(settlementField: 'addresses.*.city'), 'hu_postal_code:settlement=addresses.*.city'] as $rule) {
            self::assertSame(['addresses.1.zip' => $mismatch], $this->errors($data, ['addresses.*.zip' => [$rule]]));
        }
    }

    public function test_hungarian_message(): void
    {
        $this->app?->setLocale('hu');

        self::assertSame(
            ['zip' => 'A(z) zip irányítószám nem ehhez a településhez tartozik.'],
            $this->errors(['zip' => '7461', 'city' => 'Pécs'], ['zip' => 'hu_postal_code:settlement=city']),
        );
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function invalidParameters(): iterable
    {
        yield 'empty settlement field' => [['settlement=']];
        yield 'repeated settlement field' => [['settlement=a', 'settlement=b']];
        yield 'bare settlement' => [['settlement']];
    }

    /**
     * @param  list<string>  $parameters
     */
    #[DataProvider('invalidParameters')]
    public function test_invalid_parameters(array $parameters): void
    {
        $this->expectException(InvalidArgumentException::class);

        StringRules::make('hu_postal_code', $parameters);
    }
}
