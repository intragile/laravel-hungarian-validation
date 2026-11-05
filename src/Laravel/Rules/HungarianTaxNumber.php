<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel\Rules;

use IntrAgile\HungarianValidation\TaxNumber;
use IntrAgile\HungarianValidation\VatCode;
use IntrAgile\HungarianValidation\Violation;
use InvalidArgumentException;

/**
 * Validates a Hungarian tax number (adószám), optionally restricted to VAT codes.
 */
final class HungarianTaxNumber extends IdentifierRule
{
    /**
     * @var list<VatCode>
     */
    private readonly array $vatCodes;

    /**
     * @param  VatCode|int|list<VatCode|int>  $vatCode  Allowed VAT codes; empty allows all.
     *
     * @throws InvalidArgumentException When an integer is not a known VAT code.
     */
    public function __construct(VatCode|int|array $vatCode = [])
    {
        $codes = [];

        foreach (is_array($vatCode) ? $vatCode : [$vatCode] as $code) {
            $codes[] = $code instanceof VatCode
                ? $code
                : (VatCode::tryFrom($code) ?? throw new InvalidArgumentException(sprintf('Unknown VAT code: %d.', $code)));
        }

        $this->vatCodes = $codes;
    }

    public static function type(): string
    {
        return 'tax_number';
    }

    public static function violations(): array
    {
        return [Violation::Format, Violation::Checksum, Violation::VatCode, Violation::Region, Violation::NotAllowed];
    }

    protected function check(string $value): ?Violation
    {
        $violation = TaxNumber::validate($value);

        if ($violation !== null) {
            return $violation;
        }

        if ($this->vatCodes !== [] && ! in_array(TaxNumber::parse($value)->vatCode(), $this->vatCodes, true)) {
            return Violation::NotAllowed;
        }

        return null;
    }
}
