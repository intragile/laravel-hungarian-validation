<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Generators;

use IntrAgile\HungarianValidation\BankAccountNumber;
use IntrAgile\HungarianValidation\Support\Checksum;
use InvalidArgumentException;
use Random\Randomizer;

/**
 * Generates random domestic bank account numbers with correct GIRO check digits.
 */
final class BankAccountNumberGenerator
{
    public function __construct(private Randomizer $random = new Randomizer) {}

    /**
     * @param  int  $length  16 or 24 digits.
     *
     * @throws InvalidArgumentException When the length is not 16 or 24.
     */
    public function generate(int $length = 16): BankAccountNumber
    {
        if ($length !== 16 && $length !== 24) {
            throw new InvalidArgumentException(sprintf('Bank account numbers have 16 or 24 digits, %d given.', $length));
        }

        return BankAccountNumber::parse($this->group(8).$this->group($length - 8));
    }

    /**
     * Random digits followed by a GIRO check digit. The check digit's position
     * (8th or 16th) always carries weight 1, so it simply completes the sum to a
     * multiple of 10.
     *
     * @param  8|16  $size
     */
    private function group(int $size): string
    {
        $body = $this->random->getBytesFromString('0123456789', $size - 1);
        $check = (10 - Checksum::weighted($body, Checksum::GIRO_WEIGHTS) % 10) % 10;

        return $body.$check;
    }
}
