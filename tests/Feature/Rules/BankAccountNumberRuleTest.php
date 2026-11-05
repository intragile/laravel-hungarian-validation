<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use IntrAgile\HungarianValidation\Laravel\Rules\HungarianBankAccountNumber;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\Attributes\DataProvider;

final class BankAccountNumberRuleTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, ?Violation}>
     */
    public static function cases(): iterable
    {
        yield 'valid 16 digits' => ['11773016-11111018', null];
        yield 'valid 24 digits' => ['11773016-11111018-00000000', null];
        yield 'valid free 16th digit' => ['11773016-00000001-00000009', null];
        yield 'checksum' => ['11773016-11111019-00000000', Violation::Checksum];
        yield 'length' => ['11773016111110180', Violation::Length];
        yield 'letters' => ['1177301A-11111018', Violation::Format];
    }

    #[DataProvider('cases')]
    public function test_rule_object(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, new HungarianBankAccountNumber, $violation, HungarianBankAccountNumber::class);
    }

    #[DataProvider('cases')]
    public function test_string_rule(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, 'hu_bank_account', $violation, HungarianBankAccountNumber::class);
    }

    public function test_length_param(): void
    {
        $length = $this->expected(HungarianBankAccountNumber::messageKey(Violation::Length));

        foreach ([new HungarianBankAccountNumber(16), 'hu_bank_account:length=16'] as $rule) {
            self::assertNull($this->message('11773016-11111018', $rule));
            self::assertSame($length, $this->message('11773016-11111018-00000000', $rule));
        }

        foreach ([new HungarianBankAccountNumber(24), 'hu_bank_account:length=24'] as $rule) {
            self::assertNull($this->message('11773016-11111018-00000000', $rule));
            self::assertSame($length, $this->message('11773016-11111018', $rule));
        }
    }
}
