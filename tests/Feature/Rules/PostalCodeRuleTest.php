<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use IntrAgile\HungarianValidation\Laravel\Rules\HungarianPostalCode;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\Attributes\DataProvider;

final class PostalCodeRuleTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, ?Violation}>
     */
    public static function cases(): iterable
    {
        yield 'valid Budapest' => ['1011', null];
        yield 'valid Margitsziget' => ['1007', null];
        yield 'valid countryside' => ['7400', null];
        yield 'valid integer' => [7400, null];
        yield 'below range' => ['1008', Violation::Range];
        yield 'too short' => ['123', Violation::Format];
    }

    #[DataProvider('cases')]
    public function test_rule_object(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, new HungarianPostalCode, $violation, HungarianPostalCode::class);
    }

    #[DataProvider('cases')]
    public function test_string_rule(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, 'hu_postal_code', $violation, HungarianPostalCode::class);
    }

    public function test_budapest_only(): void
    {
        $notAllowed = $this->expected(HungarianPostalCode::messageKey(Violation::NotAllowed));

        foreach ([new HungarianPostalCode(budapestOnly: true), 'hu_postal_code:budapest'] as $rule) {
            self::assertNull($this->message('1011', $rule));
            self::assertSame($notAllowed, $this->message('7400', $rule));
            self::assertSame($this->expected(HungarianPostalCode::messageKey(Violation::Range)), $this->message('1008', $rule));
        }
    }
}
