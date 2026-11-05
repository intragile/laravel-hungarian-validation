<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Faker;

use DateTimeInterface;
use Faker\Provider\Base;
use IntrAgile\HungarianValidation\CompanyForm;
use IntrAgile\HungarianValidation\Generators\BankAccountNumberGenerator;
use IntrAgile\HungarianValidation\Generators\CompanyRegistrationNumberGenerator;
use IntrAgile\HungarianValidation\Generators\EuVatNumberGenerator;
use IntrAgile\HungarianValidation\Generators\IbanGenerator;
use IntrAgile\HungarianValidation\Generators\PostalCodeGenerator;
use IntrAgile\HungarianValidation\Generators\TajNumberGenerator;
use IntrAgile\HungarianValidation\Generators\TaxIdentificationNumberGenerator;
use IntrAgile\HungarianValidation\Generators\TaxNumberGenerator;
use IntrAgile\HungarianValidation\VatCode;
use InvalidArgumentException;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Faker formatters that produce valid Hungarian identifiers in their formatted form.
 *
 * Every call seeds its random engine from Faker's own generator, so the output
 * is reproducible with `$faker->seed()`.
 */
final class HungarianIdentifierProvider extends Base
{
    /**
     * @param  int|null  $vatCode  Fixed VAT code (1-5), or null for a random one.
     *
     * @throws InvalidArgumentException When the VAT code is unknown.
     */
    public function huTaxNumber(?int $vatCode = null): string
    {
        $code = $vatCode === null
            ? null
            : (VatCode::tryFrom($vatCode) ?? throw new InvalidArgumentException(sprintf('Unknown VAT code: %d.', $vatCode)));

        return (new TaxNumberGenerator($this->randomizer()))->generate($code)->format();
    }

    public function huEuVatNumber(): string
    {
        return (new EuVatNumberGenerator($this->randomizer()))->generate()->format();
    }

    /**
     * @throws InvalidArgumentException When the birth date is before 1867-01-01 or in the future.
     */
    public function huTaxId(?DateTimeInterface $birthDate = null): string
    {
        return (new TaxIdentificationNumberGenerator($this->randomizer()))->generate($birthDate)->format();
    }

    public function huTajNumber(): string
    {
        return (new TajNumberGenerator($this->randomizer()))->generate()->format();
    }

    /**
     * @param  int  $length  16 or 24 digits.
     * @param  bool  $formatted  Separate the 8-digit groups with hyphens.
     *
     * @throws InvalidArgumentException When the length is not 16 or 24.
     */
    public function huBankAccountNumber(int $length = 16, bool $formatted = true): string
    {
        $account = (new BankAccountNumberGenerator($this->randomizer()))->generate($length);

        return $formatted ? $account->format() : $account->value();
    }

    public function huIban(): string
    {
        return (new IbanGenerator($this->randomizer()))->generate()->format();
    }

    /**
     * @param  string|null  $form  Two-digit company form code, e.g. "09", or null for a random one.
     *
     * @throws InvalidArgumentException When the company form is unknown.
     */
    public function huCompanyRegistrationNumber(?string $form = null): string
    {
        $companyForm = $form === null
            ? null
            : (CompanyForm::tryFrom($form) ?? throw new InvalidArgumentException(sprintf('Unknown company form: %s.', $form)));

        return (new CompanyRegistrationNumberGenerator($this->randomizer()))->generate($companyForm)->format();
    }

    public function huPostalCode(bool $budapest = false): string
    {
        return (new PostalCodeGenerator($this->randomizer()))->generate($budapest)->format();
    }

    private function randomizer(): Randomizer
    {
        return new Randomizer(new Mt19937($this->generator->numberBetween(0, PHP_INT_MAX)));
    }
}
