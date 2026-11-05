<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

use IntrAgile\HungarianValidation\Support\Normalizer;

/**
 * Hungarian company registration number (cégjegyzékszám).
 *
 * Ten digits in the form CC-FF-NNNNNN: the court of registration, the company
 * form code from 21/2006. (V. 18.) IM rendelet, annex 2, and a six-digit
 * serial. A leading "Cg." prefix is accepted in any letter case.
 *
 * There is no check digit, so a valid number is one that could exist, not one
 * that is known to exist.
 */
final readonly class CompanyRegistrationNumber implements Identifier
{
    private const string TYPE = 'company registration number';

    /**
     * Matches an optional "Cg." prefix, with optional leading whitespace.
     */
    private const string PREFIX = '/^[\s\x{00A0}\x{202F}]*cg\.?/iu';

    /**
     * @param  non-empty-string  $serial
     */
    private function __construct(
        private CompanyCourt $court,
        private CompanyForm $form,
        private string $serial,
    ) {}

    public static function parse(string $value): static
    {
        $violation = self::validate($value);

        if ($violation !== null) {
            throw new InvalidIdentifierException($violation, self::TYPE);
        }

        $digits = self::normalize($value);

        /** @var non-empty-string $serial */
        $serial = substr($digits, 4);

        return new self(
            CompanyCourt::from(substr($digits, 0, 2)),
            CompanyForm::from(substr($digits, 2, 2)),
            $serial,
        );
    }

    public static function tryParse(string $value): ?static
    {
        try {
            return self::parse($value);
        } catch (InvalidIdentifierException) {
            return null;
        }
    }

    public static function isValid(string $value): bool
    {
        return self::validate($value) === null;
    }

    public static function validate(string $value): ?Violation
    {
        $digits = self::normalize($value);

        if (preg_match('/^\d{10}\z/', $digits) !== 1) {
            return Violation::Format;
        }

        if (CompanyCourt::tryFrom(substr($digits, 0, 2)) === null) {
            return Violation::Court;
        }

        if (CompanyForm::tryFrom(substr($digits, 2, 2)) === null) {
            return Violation::CompanyForm;
        }

        return null;
    }

    public function court(): CompanyCourt
    {
        return $this->court;
    }

    public function form(): CompanyForm
    {
        return $this->form;
    }

    /**
     * The six-digit serial within the court and company form.
     */
    public function serial(): string
    {
        return $this->serial;
    }

    public function value(): string
    {
        return $this->court->value.$this->form->value.$this->serial;
    }

    public function format(): string
    {
        return sprintf('%s-%s-%s', $this->court->value, $this->form->value, $this->serial);
    }

    public function equals(Identifier $other): bool
    {
        return $other instanceof self && $other->value() === $this->value();
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Strips the "Cg." prefix, then the separators. The prefix must go first
     * because compacting keeps letters.
     */
    private static function normalize(string $value): string
    {
        return Normalizer::compact(preg_replace(self::PREFIX, '', $value) ?? $value);
    }
}
