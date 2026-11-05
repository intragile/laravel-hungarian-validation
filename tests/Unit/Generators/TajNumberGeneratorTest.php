<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit\Generators;

use IntrAgile\HungarianValidation\Generators\TajNumberGenerator;
use IntrAgile\HungarianValidation\TajNumber;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * The generator takes no options, so only the validity of its output is checked.
 */
final class TajNumberGeneratorTest extends TestCase
{
    public function test_generates_valid_numbers(): void
    {
        $generator = new TajNumberGenerator(new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 500; $i++) {
            $taj = $generator->generate();

            self::assertMatchesRegularExpression('/^\d{9}$/', $taj->value());
            self::assertTrue(TajNumber::isValid($taj->value()));
        }
    }
}
