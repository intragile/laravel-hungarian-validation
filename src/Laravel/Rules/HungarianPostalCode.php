<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Support\Arr;
use IntrAgile\HungarianValidation\PostalCode;
use IntrAgile\HungarianValidation\Violation;

/**
 * Validates a Hungarian postal code (irányítószám), optionally restricted to
 * Budapest and matched against the settlement held by another field.
 *
 * The settlement check fails only when the gazetteer knows the postal code and
 * the settlement is not among its settlements; an unknown code passes, see
 * {@see PostalCode::matchesSettlement()}. A missing, empty or non-string
 * settlement field skips the check: whether the field is required is up to
 * its own rules.
 */
final class HungarianPostalCode extends IdentifierRule implements DataAwareRule
{
    /**
     * @var array<array-key, mixed>
     */
    private array $data = [];

    /**
     * @param  string|null  $settlementField  Dot-notation path of the settlement field.
     */
    public function __construct(
        private readonly bool $budapestOnly = false,
        private readonly ?string $settlementField = null,
    ) {}

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
        return 'postal_code';
    }

    public static function violations(): array
    {
        return [Violation::Format, Violation::Range, Violation::NotAllowed, Violation::SettlementMismatch];
    }

    protected function check(string $value): ?Violation
    {
        $violation = PostalCode::validate($value);

        if ($violation !== null) {
            return $violation;
        }

        $postalCode = PostalCode::parse($value);

        if ($this->budapestOnly && ! $postalCode->isBudapest()) {
            return Violation::NotAllowed;
        }

        if ($this->settlementField === null) {
            return null;
        }

        $settlement = Arr::get($this->data, FieldPath::resolve($this->settlementField, $this->attribute));

        if (! is_string($settlement) || trim($settlement) === '') {
            return null;
        }

        return $postalCode->matchesSettlement($settlement) === false ? Violation::SettlementMismatch : null;
    }
}
