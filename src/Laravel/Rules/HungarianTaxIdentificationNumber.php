<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel\Rules;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Support\Arr;
use IntrAgile\HungarianValidation\TaxIdentificationNumber;
use IntrAgile\HungarianValidation\Violation;
use InvalidArgumentException;

/**
 * Validates a Hungarian tax identification number (adóazonosító jel),
 * optionally against a birth date given directly or held by another field.
 *
 * Only the calendar date of the birth date matters. A missing or unparsable
 * date in the referenced field fails with {@see Violation::BirthDateMismatch}.
 */
final class HungarianTaxIdentificationNumber extends IdentifierRule implements DataAwareRule
{
    /**
     * @var array<array-key, mixed>
     */
    private array $data = [];

    /**
     * @param  string|null  $birthDateField  Dot-notation path of the birth date field.
     * @param  DateTimeInterface|null  $birthDate  A fixed birth date.
     *
     * @throws InvalidArgumentException When both a field and a fixed date are given.
     */
    public function __construct(
        private readonly ?string $birthDateField = null,
        private readonly ?DateTimeInterface $birthDate = null,
    ) {
        if ($birthDateField !== null && $birthDate !== null) {
            throw new InvalidArgumentException('Pass either a birth date field or a birth date, not both.');
        }
    }

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
        return 'tax_id';
    }

    public static function violations(): array
    {
        return [Violation::Format, Violation::Checksum, Violation::BirthDate, Violation::BirthDateMismatch];
    }

    protected function check(string $value): ?Violation
    {
        if ($this->birthDateField === null) {
            return TaxIdentificationNumber::validate($value, $this->birthDate);
        }

        $violation = TaxIdentificationNumber::validate($value);

        if ($violation !== null) {
            return $violation;
        }

        $birthDate = self::toDate(Arr::get($this->data, $this->birthDateField));

        if ($birthDate === null) {
            return Violation::BirthDateMismatch;
        }

        return TaxIdentificationNumber::validate($value, $birthDate);
    }

    /**
     * Interprets a field value as a date; strings that PHP parses only with
     * warnings (such as 1980-02-30) are rejected rather than rolled over.
     */
    private static function toDate(mixed $value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }

        $errors = DateTimeImmutable::getLastErrors();

        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        return $date;
    }
}
