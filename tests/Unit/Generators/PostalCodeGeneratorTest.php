<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit\Generators;

use IntrAgile\HungarianValidation\Generators\PostalCodeGenerator;
use IntrAgile\HungarianValidation\PostalCode;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class PostalCodeGeneratorTest extends TestCase
{
    public function test_generates_valid_values(): void
    {
        $generator = new PostalCodeGenerator(new Randomizer(new Mt19937(42)));
        $countryside = 0;

        for ($i = 0; $i < 500; $i++) {
            $code = $generator->generate();

            self::assertTrue(PostalCode::isValid($code->value()));

            if (! $code->isBudapest()) {
                $countryside++;
            }
        }

        self::assertGreaterThan(0, $countryside);
    }

    public function test_budapest_option(): void
    {
        $generator = new PostalCodeGenerator(new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 500; $i++) {
            $code = $generator->generate(budapest: true);

            self::assertTrue(PostalCode::isValid($code->value()));
            self::assertTrue($code->isBudapest());
            self::assertNotNull($code->budapestDistrict());
        }
    }
}
