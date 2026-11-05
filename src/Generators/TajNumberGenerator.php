<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Generators;

use IntrAgile\HungarianValidation\Support\Checksum;
use IntrAgile\HungarianValidation\TajNumber;
use Random\Randomizer;

/**
 * Generates random TAJ numbers with a correct check digit.
 */
final class TajNumberGenerator
{
    public function __construct(private Randomizer $random = new Randomizer) {}

    public function generate(): TajNumber
    {
        $body = $this->random->getBytesFromString('0123456789', 8);
        $check = Checksum::weighted($body, TajNumber::WEIGHTS) % 10;

        return TajNumber::parse($body.$check);
    }
}
