<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use IntrAgile\HungarianValidation\Laravel\Rules\HungarianBankAccountNumber;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianTaxNumber;
use IntrAgile\HungarianValidation\Laravel\StringRules;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

final class StringRuleParametersTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function invalidParameters(): iterable
    {
        yield 'unknown key' => ['hu_tax_number', ['vatcode=2']];
        yield 'bare value' => ['hu_tax_number', ['2']];
        yield 'unknown VAT code' => ['hu_tax_number', ['vat_code=9']];
        yield 'non-numeric VAT code' => ['hu_tax_number', ['vat_code=two']];
        yield 'parameter on a rule without parameters' => ['hu_taj', ['strict']];
        yield 'IBAN parameter' => ['hu_iban', ['country=DE']];
        yield 'empty field reference' => ['hu_eu_vat_number', ['matches=']];
        yield 'repeated field reference' => ['hu_tax_id', ['birth_date=a', 'birth_date=b']];
        yield 'unsupported length' => ['hu_bank_account', ['length=20']];
        yield 'non-numeric length' => ['hu_bank_account', ['length=long']];
        yield 'unknown company form' => ['hu_company_number', ['form=99']];
        yield 'flag with value' => ['hu_postal_code', ['budapest=yes']];
        yield 'unknown rule' => ['hu_passport', []];
    }

    /**
     * @param  list<string>  $parameters
     */
    #[DataProvider('invalidParameters')]
    public function test_invalid_parameters_are_rejected(string $name, array $parameters): void
    {
        $this->expectException(InvalidArgumentException::class);

        StringRules::make($name, $parameters);
    }

    public function test_invalid_parameter_surfaces_during_validation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->message('10773381-2-44', 'hu_tax_number:vatcode=2');
    }

    public function test_parameters_tolerate_whitespace(): void
    {
        self::assertNull($this->message('10773381-2-44', 'hu_tax_number:vat_code = 2'));
    }

    public function test_rule_objects_reject_invalid_options(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HungarianTaxNumber(9);
    }

    public function test_bank_account_rule_rejects_invalid_length(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HungarianBankAccountNumber(20);
    }
}
