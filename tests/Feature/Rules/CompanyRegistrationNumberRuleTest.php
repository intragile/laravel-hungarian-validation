<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature\Rules;

use IntrAgile\HungarianValidation\CompanyForm;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianCompanyRegistrationNumber;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\Attributes\DataProvider;

final class CompanyRegistrationNumberRuleTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, ?Violation}>
     */
    public static function cases(): iterable
    {
        yield 'valid' => ['01-10-041683', null];
        yield 'valid pasted' => ['  Cg. 01-10-041683', null];
        yield 'court' => ['21-10-041683', Violation::Court];
        yield 'company form' => ['01-04-041683', Violation::CompanyForm];
        yield 'too short' => ['01-10-04168', Violation::Format];
    }

    #[DataProvider('cases')]
    public function test_rule_object(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, new HungarianCompanyRegistrationNumber, $violation, HungarianCompanyRegistrationNumber::class);
    }

    #[DataProvider('cases')]
    public function test_string_rule(mixed $value, ?Violation $violation): void
    {
        $this->assertViolation($value, 'hu_company_number', $violation, HungarianCompanyRegistrationNumber::class);
    }

    public function test_form_param(): void
    {
        $notAllowed = $this->expected(HungarianCompanyRegistrationNumber::messageKey(Violation::NotAllowed));

        foreach ([new HungarianCompanyRegistrationNumber(CompanyForm::Kft), 'hu_company_number:form=09'] as $rule) {
            self::assertNull($this->message('01-09-041683', $rule));
            self::assertSame($notAllowed, $this->message('01-10-041683', $rule));
        }

        foreach ([new HungarianCompanyRegistrationNumber([CompanyForm::Kft, CompanyForm::Rt]), 'hu_company_number:form=09,form=10'] as $rule) {
            self::assertNull($this->message('01-09-041683', $rule));
            self::assertNull($this->message('01-10-041683', $rule));
            self::assertSame($notAllowed, $this->message('01-06-041683', $rule));
        }
    }
}
