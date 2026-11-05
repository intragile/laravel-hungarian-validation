<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit\Generators;

use IntrAgile\HungarianValidation\EuVatNumber;
use IntrAgile\HungarianValidation\Generators\EuVatNumberGenerator;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Generated numbers are "HU" plus a base number that satisfies the rule
 * published by the Céginformációs Szolgálat
 * (https://ceginformaciosszolgalat.kormany.hu/download/4/27/02000/adoszam_adoazonosito_ellenorzes_2018.pdf).
 */
final class EuVatNumberGeneratorTest extends TestCase
{
    public function test_generates_valid_numbers(): void
    {
        $generator = new EuVatNumberGenerator(new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 500; $i++) {
            $vat = $generator->generate();

            self::assertMatchesRegularExpression('/^HU\d{8}$/', $vat->value());
            self::assertTrue(EuVatNumber::isValid($vat->value()));
        }
    }
}
