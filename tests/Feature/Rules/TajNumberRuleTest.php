<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use IntrAgile\HungarianValidation\Laravel\Rules\HungarianTajNumber;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\Attributes\DataProvider;

final class TajNumberRuleTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, ?Violation}>
     */
    public static function cases(): iterable
    {
        yield 'valid' => ['123 456 788', null];
        yield 'valid integer' => [123456788, null];
        yield 'checksum' => ['123456789', Violation::Checksum];
        yield 'too short' => ['12345678', Violation::Format];
        yield 'letters' => ['12345678a', Violation::Format];
    }

    #[DataProvider('cases')]
    public function test_rule_object(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, new HungarianTajNumber, $violation, HungarianTajNumber::class);
    }

    #[DataProvider('cases')]
    public function test_string_rule(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, 'hu_taj', $violation, HungarianTajNumber::class);
    }
}
