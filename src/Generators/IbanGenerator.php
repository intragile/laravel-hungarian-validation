<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Generators;

use IntrAgile\HungarianValidation\Iban;
use Random\Randomizer;

/**
 * Generates random Hungarian IBANs from random 16- or 24-digit account numbers.
 */
final class IbanGenerator
{
    public function __construct(private Randomizer $random = new Randomizer) {}

    public function generate(): Iban
    {
        $length = $this->random->getInt(0, 1) === 0 ? 16 : 24;

        return Iban::fromBankAccount((new BankAccountNumberGenerator($this->random))->generate($length));
    }
}
