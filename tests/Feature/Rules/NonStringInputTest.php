<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use IntrAgile\HungarianValidation\Laravel\Rules\HungarianTajNumber;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianTaxNumber;
use IntrAgile\HungarianValidation\Laravel\StringRules;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

final class NonStringInputTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonStrings(): iterable
    {
        yield 'null' => [null];
        yield 'empty array' => [[]];
        yield 'array of a valid value' => [['10773381244']];
        yield 'float' => [1.5];
        yield 'float of a valid value' => [10773381244.0];
        yield 'true' => [true];
        yield 'object' => [new stdClass];
        yield 'negative integer' => [-10773381244];
    }

    public function test_integer_is_accepted(): void
    {
        self::assertNull($this->message(10773381244, new HungarianTaxNumber));
        self::assertNull($this->message(10773381244, 'hu_tax_number'));
    }

    #[DataProvider('nonStrings')]
    public function test_non_string_input(mixed $value): void
    {
        $format = $this->expected(HungarianTaxNumber::messageKey(Violation::Format));

        self::assertSame($format, $this->message($value, new HungarianTaxNumber));
        self::assertSame($format, $this->message($value, 'hu_tax_number'));
    }

    public function test_integer_is_not_zero_padded(): void
    {
        $format = $this->expected(HungarianTajNumber::messageKey(Violation::Format));

        self::assertSame($format, $this->message(17, new HungarianTajNumber));
        self::assertSame($format, $this->message(17, 'hu_taj'));
    }

    #[DataProvider('nonStrings')]
    public function test_every_rule_rejects_non_string_input(mixed $value): void
    {
        foreach (array_keys(StringRules::rules()) as $name) {
            self::assertSame(Violation::Format, StringRules::make($name)->violationFor($value), $name);
        }
    }
}
