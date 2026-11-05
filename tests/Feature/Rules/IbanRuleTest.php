<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use IntrAgile\HungarianValidation\Laravel\Rules\HungarianIban;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\Attributes\DataProvider;

final class IbanRuleTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, ?Violation}>
     */
    public static function cases(): iterable
    {
        yield 'valid' => ['HU42 1177 3016 1111 1018 0000 0000', null];
        yield 'valid lower case' => ['hu93107000792142148011110000', null];
        yield 'checksum' => ['HU42117730161111101800000001', Violation::Checksum];
        yield 'foreign' => ['DE89370400440532013000', Violation::Format];
    }

    #[DataProvider('cases')]
    public function test_rule_object(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, new HungarianIban, $violation, HungarianIban::class);
    }

    #[DataProvider('cases')]
    public function test_string_rule(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, 'hu_iban', $violation, HungarianIban::class);
    }
}
