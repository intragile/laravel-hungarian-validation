<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel\Rules;

use IntrAgile\HungarianValidation\CompanyForm;
use IntrAgile\HungarianValidation\CompanyRegistrationNumber;
use IntrAgile\HungarianValidation\Violation;

/**
 * Validates a Hungarian company registration number (cégjegyzékszám),
 * optionally restricted to company forms.
 */
final class HungarianCompanyRegistrationNumber extends IdentifierRule
{
    /**
     * @var list<CompanyForm>
     */
    private readonly array $forms;

    /**
     * @param  CompanyForm|list<CompanyForm>  $form  Allowed company forms; empty allows all.
     */
    public function __construct(CompanyForm|array $form = [])
    {
        $this->forms = is_array($form) ? $form : [$form];
    }

    public static function type(): string
    {
        return 'company_number';
    }

    public static function violations(): array
    {
        return [Violation::Format, Violation::Court, Violation::CompanyForm, Violation::NotAllowed];
    }

    protected function check(string $value): ?Violation
    {
        $violation = CompanyRegistrationNumber::validate($value);

        if ($violation !== null) {
            return $violation;
        }

        if ($this->forms !== [] && ! in_array(CompanyRegistrationNumber::parse($value)->form(), $this->forms, true)) {
            return Violation::NotAllowed;
        }

        return null;
    }
}
