<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use IntrAgile\HungarianValidation\Laravel\Rules\HungarianTaxNumber;
use IntrAgile\HungarianValidation\VatCode;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\Attributes\DataProvider;

final class TaxNumberRuleTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, ?Violation}>
     */
    public static function cases(): iterable
    {
        yield 'valid' => ['10773381-2-44', null];
        yield 'valid en dash' => ['10773381–2–44', null];
        yield 'valid compact' => ['10537914444', null];
        yield 'checksum' => ['10537915-4-44', Violation::Checksum];
        yield 'vat code' => ['10537914-9-44', Violation::VatCode];
        yield 'region' => ['10537914-4-01', Violation::Region];
        yield 'too short' => ['1053791-4-44', Violation::Format];
    }

    #[DataProvider('cases')]
    public function test_rule_object(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, new HungarianTaxNumber, $violation, HungarianTaxNumber::class);
    }

    #[DataProvider('cases')]
    public function test_string_rule(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, 'hu_tax_number', $violation, HungarianTaxNumber::class);
    }

    public function test_reason_specific_messages_hu(): void
    {
        $this->app?->setLocale('hu');

        foreach ([new HungarianTaxNumber, 'hu_tax_number'] as $rule) {
            self::assertSame('A(z) value ellenőrzőszáma hibás.', $this->message('10537915-4-44', $rule));
            self::assertSame(
                'A(z) value területi kódja (utolsó két számjegy) érvénytelen.',
                $this->message('10537914-4-01', $rule),
            );
        }
    }

    public function test_reason_specific_messages_en(): void
    {
        foreach ([new HungarianTaxNumber, 'hu_tax_number'] as $rule) {
            self::assertSame('The value field has an invalid check digit.', $this->message('10537915-4-44', $rule));
            self::assertSame(
                'The value field has an invalid territorial code (last two digits).',
                $this->message('10537914-4-01', $rule),
            );
        }
    }

    public function test_attribute_display_name_is_used(): void
    {
        self::assertSame(
            ['tax_number' => 'The tax number field has an invalid check digit.'],
            $this->errors(['tax_number' => '10537915-4-44'], ['tax_number' => 'hu_tax_number']),
        );
    }

    public function test_vat_code_param(): void
    {
        $notAllowed = $this->expected(HungarianTaxNumber::messageKey(Violation::NotAllowed));

        foreach ([new HungarianTaxNumber(VatCode::General), new HungarianTaxNumber(2), 'hu_tax_number:vat_code=2'] as $rule) {
            self::assertNull($this->message('10773381-2-44', $rule));
            self::assertSame($notAllowed, $this->message('10537914-4-44', $rule));
            self::assertSame(
                $this->expected(HungarianTaxNumber::messageKey(Violation::Checksum)),
                $this->message('10537915-4-44', $rule),
            );
        }
    }

    public function test_vat_code_param_with_several_values(): void
    {
        foreach ([new HungarianTaxNumber([VatCode::General, 4]), 'hu_tax_number:vat_code=2,vat_code=4'] as $rule) {
            self::assertNull($this->message('10773381-2-44', $rule));
            self::assertNull($this->message('10537914-4-44', $rule));
            self::assertSame(
                $this->expected(HungarianTaxNumber::messageKey(Violation::NotAllowed)),
                $this->message('17780010-5-44', $rule),
            );
        }
    }
}
