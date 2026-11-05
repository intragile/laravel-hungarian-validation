<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit\Generators;

use IntrAgile\HungarianValidation\BankAccountNumber;
use IntrAgile\HungarianValidation\Generators\BankAccountNumberGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class BankAccountNumberGeneratorTest extends TestCase
{
    public function test_generates_valid_16_digit_numbers_by_default(): void
    {
        $generator = new BankAccountNumberGenerator(new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 500; $i++) {
            $account = $generator->generate();

            self::assertSame(16, $account->length());
            self::assertTrue(BankAccountNumber::isValid($account->value()));
        }
    }

    public function test_generates_valid_24_digit_numbers(): void
    {
        $generator = new BankAccountNumberGenerator(new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 500; $i++) {
            $account = $generator->generate(24);

            self::assertSame(24, $account->length());
            self::assertTrue(BankAccountNumber::isValid($account->value()));
        }
    }

    public function test_rejects_unsupported_length(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new BankAccountNumberGenerator)->generate(17);
    }
}
