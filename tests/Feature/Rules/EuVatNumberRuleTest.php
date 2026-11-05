<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use IntrAgile\HungarianValidation\Laravel\Rules\HungarianEuVatNumber;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\Attributes\DataProvider;

final class EuVatNumberRuleTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, ?Violation}>
     */
    public static function cases(): iterable
    {
        yield 'valid' => ['HU17780010', null];
        yield 'valid pasted' => ['hu 1778 0010', null];
        yield 'checksum' => ['HU17780011', Violation::Checksum];
        yield 'foreign' => ['DE123456789', Violation::Format];
    }

    #[DataProvider('cases')]
    public function test_rule_object(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, new HungarianEuVatNumber, $violation, HungarianEuVatNumber::class);
    }

    #[DataProvider('cases')]
    public function test_string_rule(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, 'hu_eu_vat_number', $violation, HungarianEuVatNumber::class);
    }

    public function test_eu_vat_matches_field(): void
    {
        $rules = ['vat' => [new HungarianEuVatNumber('tax')]];
        $mismatch = $this->expected(HungarianEuVatNumber::messageKey(Violation::TaxNumberMismatch), 'vat');

        self::assertSame([], $this->errors(['vat' => 'HU10773381', 'tax' => '10773381-2-44'], $rules));
        self::assertSame([], $this->errors(['vat' => 'HU10773381', 'tax' => 10773381244], $rules));
        self::assertSame(['vat' => $mismatch], $this->errors(['vat' => 'HU10773381', 'tax' => '10537914-4-44'], $rules));
        self::assertSame(['vat' => $mismatch], $this->errors(['vat' => 'HU10773381'], $rules));
        self::assertSame(['vat' => $mismatch], $this->errors(['vat' => 'HU10773381', 'tax' => '10773381-2-01'], $rules));
        self::assertSame(['vat' => $mismatch], $this->errors(['vat' => 'HU10773381', 'tax' => ['10773381-2-44']], $rules));
    }

    public function test_eu_vat_matches_field_string_rule(): void
    {
        $rules = ['vat' => 'hu_eu_vat_number:matches=company.tax'];
        $mismatch = $this->expected(HungarianEuVatNumber::messageKey(Violation::TaxNumberMismatch), 'vat');

        self::assertSame([], $this->errors(['vat' => 'HU10773381', 'company' => ['tax' => '10773381-2-44']], $rules));
        self::assertSame(['vat' => $mismatch], $this->errors(['vat' => 'HU10773381', 'company' => ['tax' => '10537914-4-44']], $rules));
        self::assertSame(['vat' => $mismatch], $this->errors(['vat' => 'HU10773381'], $rules));
    }

    public function test_vat_group_member_does_not_match(): void
    {
        $rules = ['vat' => 'hu_eu_vat_number:matches=tax'];

        self::assertSame([], $this->errors(['vat' => 'HU17780010', 'tax' => '17780010-5-44'], $rules));
        self::assertArrayHasKey('vat', $this->errors(['vat' => 'HU17780010', 'tax' => '10537914-4-44'], $rules));
    }
}
