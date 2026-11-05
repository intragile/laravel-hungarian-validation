<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit\Generators;

use IntrAgile\HungarianValidation\Generators\TaxNumberGenerator;
use IntrAgile\HungarianValidation\InvalidIdentifierException;
use IntrAgile\HungarianValidation\TaxNumber;
use IntrAgile\HungarianValidation\VatCode;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Generated numbers must satisfy the rule published by the Céginformációs
 * Szolgálat
 * (https://ceginformaciosszolgalat.kormany.hu/download/4/27/02000/adoszam_adoazonosito_ellenorzes_2018.pdf),
 * so validity is checked through TaxNumber::isValid().
 */
final class TaxNumberGeneratorTest extends TestCase
{
    public function test_generates_valid_numbers(): void
    {
        $generator = new TaxNumberGenerator(new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 500; $i++) {
            $taxNumber = $generator->generate();

            self::assertMatchesRegularExpression('/^\d{11}$/', $taxNumber->value());
            self::assertTrue(TaxNumber::isValid($taxNumber->value()));
        }
    }

    public function test_honours_requested_parts(): void
    {
        $generator = new TaxNumberGenerator(new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 50; $i++) {
            $taxNumber = $generator->generate(VatCode::General, '13');

            self::assertSame(VatCode::General, $taxNumber->vatCode());
            self::assertSame('13', $taxNumber->regionCode());
        }
    }

    public function test_rejects_unknown_region(): void
    {
        $generator = new TaxNumberGenerator(new Randomizer(new Mt19937(42)));

        try {
            $generator->generate(region: '21');
            self::fail('Expected InvalidIdentifierException.');
        } catch (InvalidIdentifierException $e) {
            self::assertSame(Violation::Region, $e->violation);
        }
    }
}
