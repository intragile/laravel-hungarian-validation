<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit\Generators;

use IntrAgile\HungarianValidation\CompanyCourt;
use IntrAgile\HungarianValidation\CompanyForm;
use IntrAgile\HungarianValidation\CompanyRegistrationNumber;
use IntrAgile\HungarianValidation\Generators\CompanyRegistrationNumberGenerator;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class CompanyRegistrationNumberGeneratorTest extends TestCase
{
    public function test_generates_valid_values(): void
    {
        $generator = new CompanyRegistrationNumberGenerator(new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 500; $i++) {
            $number = $generator->generate();

            self::assertTrue(CompanyRegistrationNumber::isValid($number->format()));
            self::assertTrue(CompanyRegistrationNumber::isValid($number->value()));
        }
    }

    public function test_options_are_honoured(): void
    {
        $generator = new CompanyRegistrationNumberGenerator(new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 50; $i++) {
            $number = $generator->generate(CompanyForm::Kft, CompanyCourt::from('13'));

            self::assertSame(CompanyForm::Kft, $number->form());
            self::assertSame(CompanyCourt::Pest, $number->court());
            self::assertMatchesRegularExpression('/^\d{6}$/', $number->serial());
        }

        for ($i = 0; $i < 50; $i++) {
            self::assertSame(CompanyForm::Bt, $generator->generate(CompanyForm::Bt)->form());
            self::assertSame(CompanyCourt::Zala, $generator->generate(court: CompanyCourt::Zala)->court());
        }
    }

    public function test_is_deterministic_with_seed(): void
    {
        $first = new CompanyRegistrationNumberGenerator(new Randomizer(new Mt19937(42)));
        $second = new CompanyRegistrationNumberGenerator(new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 20; $i++) {
            self::assertSame($first->generate()->value(), $second->generate()->value());
        }
    }
}
