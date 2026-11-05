<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use Illuminate\Support\Facades\Validator;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianTaxNumber;
use IntrAgile\HungarianValidation\Violation;

final class StringRuleMessagesTest extends RuleTestCase
{
    public function test_custom_message_wins(): void
    {
        self::assertSame(
            ['tax' => 'X'],
            $this->errors(['tax' => '10537915-4-44'], ['tax' => 'hu_tax_number'], ['tax.hu_tax_number' => 'X']),
        );
        self::assertSame(
            ['tax' => 'Y tax'],
            $this->errors(['tax' => '10537915-4-44'], ['tax' => 'hu_tax_number'], ['hu_tax_number' => 'Y :attribute']),
        );
    }

    public function test_custom_message_from_translator_wins(): void
    {
        $this->app?->make('translator')->addLines(['validation.custom.tax.hu_tax_number' => 'Custom :attribute'], 'en');

        self::assertSame(['tax' => 'Custom tax'], $this->errors(['tax' => '10537915-4-44'], ['tax' => 'hu_tax_number']));
    }

    public function test_application_rule_message_wins(): void
    {
        $this->app?->make('translator')->addLines(['validation.hu_tax_number' => 'App :attribute'], 'en');

        self::assertSame(['tax' => 'App tax'], $this->errors(['tax' => '10537915-4-44'], ['tax' => 'hu_tax_number']));
    }

    public function test_custom_message_for_rule_object(): void
    {
        self::assertSame(
            ['tax' => 'Z'],
            $this->errors(['tax' => '10537915-4-44'], ['tax' => [new HungarianTaxNumber]], ['tax.'.HungarianTaxNumber::class => 'Z']),
        );
    }

    public function test_attributes_keep_their_own_reason(): void
    {
        self::assertSame(
            [
                'a' => $this->expected(HungarianTaxNumber::messageKey(Violation::Checksum), 'a'),
                'b' => $this->expected(HungarianTaxNumber::messageKey(Violation::Region), 'b'),
                'c' => $this->expected(HungarianTaxNumber::messageKey(Violation::Format), 'c'),
            ],
            $this->errors(
                ['a' => '10537915-4-44', 'b' => '10537914-4-01', 'c' => 'x', 'd' => '10537914-4-44'],
                ['a' => 'hu_tax_number', 'b' => 'hu_tax_number', 'c' => 'hu_tax_number', 'd' => 'hu_tax_number'],
            ),
        );
    }

    public function test_wildcard_attributes_keep_their_own_reason(): void
    {
        $errors = $this->errors(
            ['items' => [['tax' => '10537915-4-44'], ['tax' => '10537914-4-01']]],
            ['items.*.tax' => 'hu_tax_number'],
        );

        self::assertSame(
            [
                'items.0.tax' => $this->expected(HungarianTaxNumber::messageKey(Violation::Checksum), 'items.0.tax'),
                'items.1.tax' => $this->expected(HungarianTaxNumber::messageKey(Violation::Region), 'items.1.tax'),
            ],
            $errors,
        );
    }

    public function test_validators_do_not_share_reasons(): void
    {
        $first = Validator::make(['tax' => '10537915-4-44'], ['tax' => 'hu_tax_number']);
        $second = Validator::make(['tax' => '10537914-4-01'], ['tax' => 'hu_tax_number']);

        // Run both before reading either message.
        self::assertTrue($first->fails());
        self::assertTrue($second->fails());

        self::assertSame($this->expected(HungarianTaxNumber::messageKey(Violation::Checksum), 'tax'), $first->errors()->first('tax'));
        self::assertSame($this->expected(HungarianTaxNumber::messageKey(Violation::Region), 'tax'), $second->errors()->first('tax'));
    }

    public function test_revalidation_uses_the_new_reason(): void
    {
        $validator = Validator::make(['tax' => '10537915-4-44'], ['tax' => 'hu_tax_number']);
        self::assertTrue($validator->fails());

        $validator->setData(['tax' => '10537914-4-01']);
        self::assertTrue($validator->fails());

        self::assertSame($this->expected(HungarianTaxNumber::messageKey(Violation::Region), 'tax'), $validator->errors()->first('tax'));
    }

    public function test_locale_change_between_validations(): void
    {
        self::assertSame('The tax field has an invalid check digit.', $this->message('10537915-4-44', 'hu_tax_number', 'tax'));

        $this->app?->setLocale('hu');

        self::assertSame('A(z) tax ellenőrzőszáma hibás.', $this->message('10537915-4-44', 'hu_tax_number', 'tax'));
    }
}
