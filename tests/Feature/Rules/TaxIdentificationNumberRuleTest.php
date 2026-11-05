<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Carbon;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianTaxIdentificationNumber;
use IntrAgile\HungarianValidation\Violation;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * 8412721233 embeds 1980-01-01 (derivation in TaxIdentificationNumberTest).
 */
final class TaxIdentificationNumberRuleTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, ?Violation}>
     */
    public static function cases(): iterable
    {
        yield 'valid' => ['8412721233', null];
        yield 'valid pasted' => ['8412-721-233', null];
        yield 'checksum' => ['8412721234', Violation::Checksum];
        yield 'future birth date' => ['8999990001', Violation::BirthDate];
        yield 'too short' => ['841272123', Violation::Format];
    }

    #[DataProvider('cases')]
    public function test_rule_object(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, new HungarianTaxIdentificationNumber, $violation, HungarianTaxIdentificationNumber::class);
    }

    #[DataProvider('cases')]
    public function test_string_rule(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, 'hu_tax_id', $violation, HungarianTaxIdentificationNumber::class);
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function birthDates(): iterable
    {
        yield 'matching string' => ['1980-01-01', true];
        yield 'matching string with time' => ['1980-01-01 23:30:00', true];
        yield 'matching DateTime' => [new DateTimeImmutable('1980-01-01 23:30', new DateTimeZone('Pacific/Auckland')), true];
        yield 'matching Carbon' => [Carbon::create(1980, 1, 1), true];
        yield 'other date' => ['1980-01-02', false];
        yield 'rolled-over date' => ['1979-12-32', false];
        yield 'impossible date' => ['1980-02-30', false];
        yield 'Hungarian date' => ['1980.01.01.', true];
        yield 'Hungarian date without trailing dot' => ['1980.01.01', true];
        yield 'Hungarian date with spaces' => ['1980. 01. 01.', true];
        yield 'Hungarian date with single digits' => ['1980. 1. 1.', true];
        yield 'Hungarian impossible date' => ['1980.02.30.', false];
        yield 'not a date' => ['not a date', false];
        yield 'empty' => ['', false];
        yield 'null' => [null, false];
        yield 'integer' => [19800101, false];
    }

    #[DataProvider('birthDates')]
    public function test_tax_id_birth_date_field(mixed $birthDate, bool $passes): void
    {
        $mismatch = $this->expected(HungarianTaxIdentificationNumber::messageKey(Violation::BirthDateMismatch), 'tax id');

        foreach ([new HungarianTaxIdentificationNumber('person.born_on'), 'hu_tax_id:birth_date=person.born_on'] as $rule) {
            $errors = $this->errors(
                ['tax_id' => '8412721233', 'person' => ['born_on' => $birthDate]],
                ['tax_id' => [$rule]],
            );

            self::assertSame($passes ? [] : ['tax_id' => $mismatch], $errors);
        }
    }

    public function test_missing_birth_date_field(): void
    {
        self::assertSame(
            ['tax_id' => $this->expected(HungarianTaxIdentificationNumber::messageKey(Violation::BirthDateMismatch), 'tax id')],
            $this->errors(['tax_id' => '8412721233'], ['tax_id' => 'hu_tax_id:birth_date=born_on']),
        );
    }

    public function test_number_errors_take_precedence_over_the_birth_date(): void
    {
        self::assertSame(
            ['tax_id' => $this->expected(HungarianTaxIdentificationNumber::messageKey(Violation::Checksum), 'tax id')],
            $this->errors(['tax_id' => '8412721234'], ['tax_id' => 'hu_tax_id:birth_date=born_on']),
        );
    }

    public function test_fixed_birth_date(): void
    {
        self::assertNull($this->message('8412721233', new HungarianTaxIdentificationNumber(birthDate: new DateTimeImmutable('1980-01-01'))));
        self::assertSame(
            $this->expected(HungarianTaxIdentificationNumber::messageKey(Violation::BirthDateMismatch)),
            $this->message('8412721233', new HungarianTaxIdentificationNumber(birthDate: new DateTimeImmutable('1980-01-02'))),
        );
    }

    public function test_field_and_fixed_date_are_exclusive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HungarianTaxIdentificationNumber('born_on', new DateTimeImmutable('1980-01-01'));
    }

    public function test_wildcard_birth_date_field(): void
    {
        $data = ['items' => [
            ['tid' => '8412721233', 'born' => '1980-01-01'],
            ['tid' => '8412721233', 'born' => '1980-01-02'],
        ]];
        $mismatch = $this->expected(HungarianTaxIdentificationNumber::messageKey(Violation::BirthDateMismatch), 'items.1.tid');

        foreach ([new HungarianTaxIdentificationNumber('items.*.born'), 'hu_tax_id:birth_date=items.*.born'] as $rule) {
            self::assertSame(['items.1.tid' => $mismatch], $this->errors($data, ['items.*.tid' => [$rule]]));
        }
    }
}
