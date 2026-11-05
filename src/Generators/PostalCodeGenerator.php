<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Generators;

use IntrAgile\HungarianValidation\PostalCode;
use Random\Randomizer;

/**
 * Generates structurally valid postal codes.
 *
 * Validity follows from the construction: the draw starts at 1010, which is
 * not a valid code and is remapped to 1007, so every valid code is equally
 * likely and no retry loop is needed.
 */
final class PostalCodeGenerator
{
    public function __construct(private readonly Randomizer $random = new Randomizer) {}

    /**
     * @param  bool  $budapest  Restrict output to Budapest district codes (1007, 1011-1239),
     *                          so the result always has a district. False means any valid code.
     */
    public function generate(bool $budapest = false): PostalCode
    {
        $number = $this->random->getInt(1010, $budapest ? 1239 : 9999);

        if ($number === 1010) {
            $number = 1007;
        }

        return PostalCode::parse((string) $number);
    }
}
