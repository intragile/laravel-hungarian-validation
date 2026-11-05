<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Support\Arr;
use IntrAgile\HungarianValidation\EuVatNumber;
use IntrAgile\HungarianValidation\TaxNumber;
use IntrAgile\HungarianValidation\Violation;

/**
 * Validates a Hungarian EU VAT number (közösségi adószám), optionally against
 * the tax number held by another field.
 *
 * A missing or invalid tax number in the referenced field fails with
 * {@see Violation::TaxNumberMismatch}.
 */
final class HungarianEuVatNumber extends IdentifierRule implements DataAwareRule
{
    /**
     * @var array<array-key, mixed>
     */
    private array $data = [];

    /**
     * @param  string|null  $matchesTaxNumberField  Dot-notation path of the tax number field.
     */
    public function __construct(private readonly ?string $matchesTaxNumberField = null) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public static function type(): string
    {
        return 'eu_vat_number';
    }

    public static function violations(): array
    {
        return [Violation::Format, Violation::Checksum, Violation::TaxNumberMismatch];
    }

    protected function check(string $value): ?Violation
    {
        $violation = EuVatNumber::validate($value);

        if ($violation !== null || $this->matchesTaxNumberField === null) {
            return $violation;
        }

        $taxNumber = Arr::get($this->data, FieldPath::resolve($this->matchesTaxNumberField, $this->attribute));
        $taxNumber = is_int($taxNumber) && $taxNumber >= 0 ? (string) $taxNumber : $taxNumber;
        $taxNumber = is_string($taxNumber) ? TaxNumber::tryParse($taxNumber) : null;

        if ($taxNumber === null || ! EuVatNumber::parse($value)->matches($taxNumber)) {
            return Violation::TaxNumberMismatch;
        }

        return null;
    }
}
