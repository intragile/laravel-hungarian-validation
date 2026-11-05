<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Generators;

use IntrAgile\HungarianValidation\CompanyCourt;
use IntrAgile\HungarianValidation\CompanyForm;
use IntrAgile\HungarianValidation\CompanyRegistrationNumber;
use Random\Randomizer;

/**
 * Generates random company registration numbers.
 *
 * Court and company form are picked from the known codes unless given, so every
 * result is valid without retrying.
 */
final class CompanyRegistrationNumberGenerator
{
    public function __construct(private Randomizer $random = new Randomizer) {}

    public function generate(?CompanyForm $form = null, ?CompanyCourt $court = null): CompanyRegistrationNumber
    {
        $court ??= $this->pick(CompanyCourt::cases());
        $form ??= $this->pick(CompanyForm::cases());
        $serial = $this->random->getBytesFromString('0123456789', 6);

        return CompanyRegistrationNumber::parse($court->value.$form->value.$serial);
    }

    /**
     * @template T
     *
     * @param  non-empty-list<T>  $cases
     * @return T
     */
    private function pick(array $cases): mixed
    {
        return $cases[$this->random->getInt(0, count($cases) - 1)];
    }
}
