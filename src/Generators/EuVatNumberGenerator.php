<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Generators;

use IntrAgile\HungarianValidation\EuVatNumber;
use Random\Randomizer;

/**
 * Generates random Hungarian EU VAT numbers with a correct check digit.
 */
final class EuVatNumberGenerator
{
    public function __construct(private Randomizer $random = new Randomizer) {}

    public function generate(): EuVatNumber
    {
        $taxNumber = (new TaxNumberGenerator($this->random))->generate();

        return EuVatNumber::fromBaseNumber($taxNumber->baseNumber());
    }
}
