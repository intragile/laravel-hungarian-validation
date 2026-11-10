<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit;

use IntrAgile\HungarianValidation\CompanyCourt;
use IntrAgile\HungarianValidation\CompanyForm;
use IntrAgile\HungarianValidation\CompanyRegistrationNumber;
use IntrAgile\HungarianValidation\InvalidIdentifierException;
use IntrAgile\HungarianValidation\Violation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Company registration numbers (cégjegyzékszám) have the form CC-FF-NNNNNN:
 * the court of registration, the company form code from 21/2006. (V. 18.) IM
 * rendelet, annex 2 (https://net.jogtar.hu/jogszabaly?docid=a0600021.im), and
 * a six-digit serial. There is no check digit.
 *
 * The real numbers are those of MOL, OTP and Magyar Telekom.
 */
final class CompanyRegistrationNumberTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function realNumbers(): iterable
    {
        yield 'MOL' => ['01-10-041683'];
        yield 'OTP' => ['01-10-041585'];
        yield 'Telekom' => ['01-10-041928'];
    }

    #[DataProvider('realNumbers')]
    public function test_real_numbers(string $value): void
    {
        self::assertTrue(CompanyRegistrationNumber::isValid($value));

        $number = CompanyRegistrationNumber::parse($value);

        self::assertSame(CompanyForm::Rt, $number->form());
        self::assertSame(CompanyCourt::from('01'), $number->court());
        self::assertSame($value, $number->format());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function prefixedAndSpaced(): iterable
    {
        yield 'Cg. with space' => ['Cg. 01-10-041683'];
        yield 'Cg. with spaces as separators' => ['Cg.01 10 041683'];
        yield 'trailing dot' => ['01-10-041683.'];
        yield 'lowercase cg without dot' => ['cg 01-10-041683'];
        yield 'uppercase CG.' => ['CG.01-10-041683'];
        yield 'no-break space' => ["Cg.\u{00A0}01\u{00A0}10\u{00A0}041683"];
        yield 'en dash' => ["01\u{2013}10\u{2013}041683"];
        yield 'leading whitespace' => ['  Cg. 01-10-041683'];
    }

    #[DataProvider('prefixedAndSpaced')]
    public function test_prefix_and_spacing(string $value): void
    {
        self::assertSame('01-10-041683', CompanyRegistrationNumber::parse($value)->format());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownCourts(): iterable
    {
        yield 'code 00' => ['00-10-041683'];
        yield 'code 21' => ['21-10-041683'];
    }

    #[DataProvider('unknownCourts')]
    public function test_unknown_court(string $value): void
    {
        self::assertSame(Violation::Court, CompanyRegistrationNumber::validate($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownForms(): iterable
    {
        yield 'code 00' => ['01-00-041683'];
        yield 'code 04' => ['01-04-041683'];
        yield 'code 05' => ['01-05-041683'];
        yield 'code 13' => ['01-13-041683'];
        yield 'code 24' => ['01-24-041683'];
    }

    /**
     * Regression for erelke: codes 04, 05 and 13 are not assigned by annex 2 and
     * must be rejected, as must codes outside the 01 to 23 range.
     */
    #[DataProvider('unknownForms')]
    public function test_unknown_form(string $value): void
    {
        self::assertSame(Violation::CompanyForm, CompanyRegistrationNumber::validate($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function wrongDigitCounts(): iterable
    {
        yield 'nine digits' => ['01-10-04168'];
        yield 'eleven digits' => ['01-10-0416830'];
        yield 'empty' => [''];
        yield 'letters' => ['AB-10-041683'];
        yield 'prefix only' => ['Cg.'];
        yield 'unknown prefix' => ['cgx 01-10-041683'];
    }

    #[DataProvider('wrongDigitCounts')]
    public function test_wrong_digit_count(string $value): void
    {
        self::assertSame(Violation::Format, CompanyRegistrationNumber::validate($value));
    }

    public function test_validation_order(): void
    {
        // A format error is reported before an unknown court.
        self::assertSame(Violation::Format, CompanyRegistrationNumber::validate('123'));

        // An unknown court is reported before an unknown form.
        self::assertSame(Violation::Court, CompanyRegistrationNumber::validate('00-00-041683'));
    }

    public function test_accessors_and_value(): void
    {
        $number = CompanyRegistrationNumber::parse('01-10-041683');

        self::assertSame('041683', $number->serial());
        self::assertSame('0110041683', $number->value());
        self::assertSame($number->format(), (string) $number);

        self::assertTrue($number->equals(CompanyRegistrationNumber::parse('Cg. 01 10 041683')));
        self::assertFalse($number->equals(CompanyRegistrationNumber::parse('01-10-041585')));
    }

    public function test_parse_throws_and_try_parse_returns_null(): void
    {
        try {
            CompanyRegistrationNumber::parse('21-10-041683');
            self::fail('Expected InvalidIdentifierException.');
        } catch (InvalidIdentifierException $e) {
            self::assertSame(Violation::Court, $e->violation);
            self::assertSame('company registration number', $e->identifierType);
            self::assertSame(
                'Invalid Hungarian company registration number: unknown court of registration.',
                $e->getMessage(),
            );
        }

        self::assertNull(CompanyRegistrationNumber::tryParse('21-10-041683'));
        self::assertFalse(CompanyRegistrationNumber::isValid('21-10-041683'));
        self::assertNotNull(CompanyRegistrationNumber::tryParse('01-10-041683'));
    }

    public function test_court_cases(): void
    {
        $cases = CompanyCourt::cases();

        self::assertCount(20, $cases);
        self::assertSame(
            array_map(static fn (int $i): string => sprintf('%02d', $i), range(1, 20)),
            array_map(static fn (CompanyCourt $c): string => $c->value, $cases),
        );

        foreach ($cases as $court) {
            self::assertNotSame('', $court->label());
        }

        self::assertSame('Fővárosi Törvényszék Cégbírósága', CompanyCourt::from('01')->label());
        self::assertSame('Budapest Környéki Törvényszék Cégbírósága (Pest megye)', CompanyCourt::from('13')->label());

        foreach (CompanyCourt::cases() as $court) {
            self::assertStringNotContainsString('vármegye', $court->label());
        }
    }

    public function test_form_cases(): void
    {
        self::assertSame(
            ['01', '02', '03', '06', '07', '08', '09', '10', '11', '12', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'],
            array_map(static fn (CompanyForm $f): string => $f->value, CompanyForm::cases()),
        );

        foreach (CompanyForm::cases() as $form) {
            self::assertNotSame('', $form->label('hu'));
            self::assertNotSame('', $form->label('en'));
            self::assertNotSame($form->label('hu'), $form->label('en'));
        }

        self::assertSame('Korlátolt felelősségű társaság', CompanyForm::Kft->label('hu'));
        self::assertSame(CompanyForm::Kft->label('en'), CompanyForm::Kft->label('de'));
        self::assertSame(CompanyForm::Kft->label('en'), CompanyForm::Kft->label());
        self::assertSame(CompanyForm::Kft->label('hu'), CompanyForm::Kft->label('hu_HU'));
    }
}
