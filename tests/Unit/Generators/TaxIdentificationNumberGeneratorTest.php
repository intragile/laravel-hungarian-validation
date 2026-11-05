<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit\Generators;

use DateTimeImmutable;
use DateTimeZone;
use IntrAgile\HungarianValidation\Generators\TaxIdentificationNumberGenerator;
use IntrAgile\HungarianValidation\TaxIdentificationNumber;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class TaxIdentificationNumberGeneratorTest extends TestCase
{
    private function generator(): TaxIdentificationNumberGenerator
    {
        return new TaxIdentificationNumberGenerator(new Randomizer(new Mt19937(42)));
    }

    public function test_generates_500_valid_values(): void
    {
        $generator = $this->generator();
        $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Budapest')))->format('Y-m-d');

        for ($i = 0; $i < 500; $i++) {
            $number = $generator->generate();

            self::assertMatchesRegularExpression('/^8\d{9}$/', $number->value());
            self::assertTrue(TaxIdentificationNumber::isValid($number->value()));
            self::assertLessThanOrEqual($today, $number->birthDate()->format('Y-m-d'));
        }
    }

    public function test_honours_birth_date(): void
    {
        $generator = $this->generator();

        $number = $generator->generate(new DateTimeImmutable('1990-05-17'));
        self::assertSame('1990-05-17', $number->birthDate()->format('Y-m-d'));

        $auckland = new DateTimeImmutable('1990-05-17 23:30', new DateTimeZone('Pacific/Auckland'));
        self::assertSame('1990-05-17', $generator->generate($auckland)->birthDate()->format('Y-m-d'));
    }

    public function test_rejects_future_birth_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator()->generate(new DateTimeImmutable('+2 days'));
    }

    public function test_rejects_pre_epoch_birth_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator()->generate(new DateTimeImmutable('1866-12-31'));
    }

    public function test_never_produces_remainder_ten(): void
    {
        $generator = $this->generator();

        for ($i = 0; $i < 500; $i++) {
            $digits = $generator->generate()->value();
            $sum = 0;

            for ($position = 1; $position <= 9; $position++) {
                $sum += (int) $digits[$position - 1] * $position;
            }

            self::assertNotSame(10, $sum % 11);
            self::assertSame($sum % 11, (int) $digits[9]);
        }
    }
}
