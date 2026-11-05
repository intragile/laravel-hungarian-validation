<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Generators;

use IntrAgile\HungarianValidation\InvalidIdentifierException;
use IntrAgile\HungarianValidation\Support\Checksum;
use IntrAgile\HungarianValidation\TaxNumber;
use IntrAgile\HungarianValidation\TaxRegion;
use IntrAgile\HungarianValidation\VatCode;
use IntrAgile\HungarianValidation\Violation;
use Random\Randomizer;

/**
 * Generates random Hungarian tax numbers with a correct check digit.
 */
final class TaxNumberGenerator
{
    public function __construct(private Randomizer $random = new Randomizer) {}

    /**
     * @param  VatCode|null  $vatCode  Fixed VAT code, or null for a random one.
     * @param  string|null  $region  Fixed territorial code, or null for a random one.
     *
     * @throws InvalidIdentifierException When the territorial code is unknown.
     */
    public function generate(?VatCode $vatCode = null, ?string $region = null): TaxNumber
    {
        if ($region !== null && ! TaxRegion::isKnown($region)) {
            throw new InvalidIdentifierException(Violation::Region, 'tax number');
        }

        $body = $this->random->getBytesFromString('123456789', 1)
            .$this->random->getBytesFromString('0123456789', 6);
        $check = TaxNumber::checkDigit(Checksum::weighted($body, TaxNumber::BASE_WEIGHTS));

        $cases = VatCode::cases();
        $vatCode ??= $cases[$this->random->getInt(0, count($cases) - 1)];

        $codes = TaxRegion::codes();
        $region ??= $codes[$this->random->getInt(0, count($codes) - 1)];

        return TaxNumber::parse($body.$check.$vatCode->value.$region);
    }
}
