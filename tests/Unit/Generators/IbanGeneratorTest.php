<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit\Generators;

use IntrAgile\HungarianValidation\Generators\IbanGenerator;
use IntrAgile\HungarianValidation\Iban;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * The generator takes no options, so only the validity of its output is checked.
 */
final class IbanGeneratorTest extends TestCase
{
    public function test_generates_valid_ibans(): void
    {
        $generator = new IbanGenerator(new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 500; $i++) {
            $iban = $generator->generate();

            self::assertMatchesRegularExpression('/^HU\d{26}$/', $iban->value());
            self::assertTrue(Iban::isValid($iban->value()));
        }
    }
}
