# Laravel Hungarian Validation

Developed and maintained by [IntrAgile Kft.](https://intragile.eu)

[![Tests](https://github.com/intragile/laravel-hungarian-validation/actions/workflows/tests.yml/badge.svg)](https://github.com/intragile/laravel-hungarian-validation/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/intragile/laravel-hungarian-validation.svg)](https://packagist.org/packages/intragile/laravel-hungarian-validation)
[![PHP](https://img.shields.io/packagist/php-v/intragile/laravel-hungarian-validation.svg)](https://packagist.org/packages/intragile/laravel-hungarian-validation)
[![License](https://img.shields.io/packagist/l/intragile/laravel-hungarian-validation.svg)](LICENSE)

Validation, normalisation, formatting and test data for **Hungarian identifiers**: tax number
(adószám), EU VAT number (közösségi adószám), personal tax ID (adóazonosító jel), TAJ number,
domestic bank account number, Hungarian IBAN, company registration number (cégjegyzékszám) and
postal code (irányítószám).

[Magyar nyelvű leírás → README.hu.md](README.hu.md)

- **Real checks, not regular expressions.** Every identifier with a check digit is verified
  against the algorithm in the law or the MNB/NAV specification, along with the code lists and
  internal consistency rules (VAT code, territorial code, embedded birth date, court and
  company form). Each rule cites its source below.
- **Normalise, format and decompose.** `10773381244`, `10773381 2 44` and `10773381–2–44`
  (pasted from a PDF, with an en dash) all become `10773381-2-44`. Value objects expose the
  parts, such as the base number, VAT code and territorial code.
- **Framework-independent core.** The core is plain PHP with no dependencies. The Laravel
  layer adds rule objects, string rules, reason-specific messages in English and Hungarian,
  and a Faker provider.
- **Valid test data.** Generators and a Faker provider produce valid identifiers for seeders
  and tests.

## Why this package?

Existing PHP packages for Hungarian identifiers mostly match a regular expression. As a result
they accept tax numbers with VAT code 0, accept personal tax IDs whose check digit the law
forbids, and accept company registration numbers with form codes that do not exist. Some
implement the tax number check digit incorrectly and reject real tax numbers. None of them
covers the IBAN together with the inner GIRO check, or the rule that the second check digit of
a 24-digit account covers 16 digits. Every one of these cases is a regression test here.

## Requirements

- PHP 8.3+
- Laravel 12 or 13 for the validation rules (optional)
- `fakerphp/faker` 1.23+ for the Faker provider (optional)

## Installation

```bash
composer require intragile/laravel-hungarian-validation
```

The service provider is discovered automatically. To customise the messages:

```bash
php artisan vendor:publish --tag=hungarian-validation-lang
```

## Core usage (any PHP project)

Every identifier class has the same API:

```php
use IntrAgile\HungarianValidation\TaxNumber;
use IntrAgile\HungarianValidation\Violation;

TaxNumber::isValid('10773381 2 44');            // true
TaxNumber::validate('10773380-2-44');           // Violation::Checksum
TaxNumber::tryParse('nonsense');                // null

$taxNumber = TaxNumber::parse('10773381244');   // throws InvalidIdentifierException if invalid
$taxNumber->format();                           // "10773381-2-44"
$taxNumber->value();                            // "10773381244"
$taxNumber->baseNumber();                       // "10773381"
$taxNumber->vatCode();                          // VatCode::General
$taxNumber->vatCode()->label('hu');             // "általános szabályok szerint adózó"
$taxNumber->regionCode();                       // "44"
$taxNumber->euVatNumber()->format();            // "HU10773381"
```

`validate()` returns the first rule the value breaks, as a `Violation` (`Format`, `Checksum`,
`VatCode`, `Region`, `BirthDate`, `BirthDateMismatch`, `TaxNumberMismatch`, `Court`,
`CompanyForm`, `Range`, `Length`, `NotAllowed`, `SettlementMismatch`), or `null` when the value
is valid. `parse()` throws an `InvalidIdentifierException` that carries the violation.

Separators are ignored wherever they appear: spaces (including no-break spaces), hyphens,
Unicode dashes, the minus sign, dots and slashes.

### Identifiers

| Class | Example (`format()`) | Checks | Parts |
|---|---|---|---|
| `TaxNumber` (adószám) | `10773381-2-44` | check digit of the base number, VAT code 1–5, territorial code | `baseNumber()`, `vatCode()`, `regionCode()`, `euVatNumber()` |
| `EuVatNumber` (közösségi adószám) | `HU17780010` | `HU` prefix, check digit | `baseNumber()`, `matches(TaxNumber)` |
| `TaxIdentificationNumber` (adóazonosító jel) | `8412721233` | leading 8, check digit, embedded birth date not in the future | `birthDate()`, `serial()`, `matchesBirthDate()` |
| `TajNumber` | `123 456 788` | check digit | |
| `BankAccountNumber` | `11773016-11111018` | GIRO check digits, 16 or 24 digits | `bankCode()`, `branchCode()`, `length()`, `toIban()` |
| `Iban` | `HU42 1177 3016 1111 1018 0000 0000` | `HU`, 28 characters, MOD 97-10 and the inner GIRO check | `bankAccount()`, `fromBankAccount()` |
| `CompanyRegistrationNumber` (cégjegyzékszám) | `01-10-041683` | court of registration, company form | `court()`, `form()`, `serial()` |
| `PostalCode` (irányítószám) | `1052` | structure and the issued ranges | `isBudapest()`, `budapestDistrict()`, `settlements()`, `matchesSettlement()` |

```php
TaxIdentificationNumber::validate('8412721233', new DateTimeImmutable('1980-01-01')); // null
EuVatNumber::parse('HU17780010')->matches(TaxNumber::parse('17780010-5-44'));        // true
BankAccountNumber::parse('11773016-11111018')->toIban()->format();                   // "HU42 1177 3016 ..."
CompanyRegistrationNumber::parse('Cg. 01-10-041683')->form();                        // CompanyForm::Rt
PostalCode::parse('1052')->budapestDistrict();                                        // 5
PostalCode::parse('7461')->settlements();                                             // ["Kaposvár"]
```

## Laravel validation

Each identifier has a rule object and a string rule:

| Rule object | String rule | Options |
|---|---|---|
| `new HungarianTaxNumber(vatCode: [2, 4])` | `hu_tax_number:vat_code=2,vat_code=4` | allowed VAT codes |
| `new HungarianEuVatNumber('tax_number')` | `hu_eu_vat_number:matches=tax_number` | must match the tax number in another field |
| `new HungarianTaxIdentificationNumber('born_on')` | `hu_tax_id:birth_date=born_on` | must match the birth date in another field; `birthDate:` takes a fixed date |
| `new HungarianTajNumber` | `hu_taj` | |
| `new HungarianBankAccountNumber(16)` | `hu_bank_account:length=16` | 16 or 24 digits only |
| `new HungarianIban` | `hu_iban` | |
| `new HungarianCompanyRegistrationNumber(CompanyForm::Kft)` | `hu_company_number:form=09` | allowed company forms |
| `new HungarianPostalCode(budapestOnly: true, settlementField: 'city')` | `hu_postal_code:budapest,settlement=city` | Budapest only; must belong to the settlement in another field |

The rule classes live in `IntrAgile\HungarianValidation\Laravel\Rules`.

```php
$request->validate([
    'tax_number' => ['required', 'hu_tax_number:vat_code=2'],
    'eu_vat' => ['nullable', 'hu_eu_vat_number:matches=tax_number'],
    'people.*.tax_id' => ['required', 'hu_tax_id:birth_date=people.*.born_on'],
    'iban' => ['required', new HungarianIban],
]);
```

- **Messages name the reason:** an invalid format, a check digit mismatch and an unknown
  territorial code each get their own message, in English and Hungarian. Messages you define
  yourself (inline, `validation.custom.*` or `validation.hu_tax_number`) take precedence.
- **Field references** accept dot notation and wildcards (`people.*.born_on`).
- **Birth dates** are accepted as `DateTimeInterface`, ISO dates or Hungarian notation
  (`1980.01.01.`, `1980. 01. 01.`). Only the calendar date counts.
- **Input types:** strings and non-negative integers are validated. Integers are not
  zero-padded, so a TAJ number with leading zeros must be submitted as a string.
- **Invalid rule options** (an unknown VAT code, an unknown parameter) throw an
  `InvalidArgumentException`, because they are programming errors.

## Test data

```php
use IntrAgile\HungarianValidation\Generators\TaxNumberGenerator;
use IntrAgile\HungarianValidation\VatCode;

(new TaxNumberGenerator)->generate(VatCode::General, '13')->format();  // e.g. "24837162-2-13"
```

Each identifier has a generator in `IntrAgile\HungarianValidation\Generators`. Pass a seeded
`Random\Randomizer` for reproducible output. In Laravel, the Faker provider is registered
automatically:

```php
fake()->huTaxNumber();               // "10773381-2-44"
fake()->huTaxNumber(vatCode: 2);
fake()->huEuVatNumber();
fake()->huTaxId(new DateTimeImmutable('1990-06-15'));
fake()->huTajNumber();
fake()->huBankAccountNumber(24);
fake()->huIban();
fake()->huCompanyRegistrationNumber('09');
fake()->huPostalCode(budapest: true);
```

Outside Laravel: `$faker->addProvider(new HungarianIdentifierProvider($faker));`.

## Rules and sources

| Identifier | Rule | Source |
|---|---|---|
| Tax number | Weights 9, 7, 3, 1, 9, 7, 3 on digits 1–7; digit 8 = (10 − sum mod 10) mod 10. VAT code 1–5. Territorial code 02–20, 22–44 or 51. | [Céginformációs Szolgálat](https://ceginformaciosszolgalat.kormany.hu/download/4/27/02000/adoszam_adoazonosito_ellenorzes_2018.pdf); NAV Online Számla `common.xsd`; NAV's notice on taxpayer data (*afaalany_taj.html*, archived 2008–2021) |
| EU VAT number | `HU` + the 8-digit base number, same check digit | as above |
| Personal tax ID | Digit 1 = 8; digits 2–6 = days since 1867-01-01; digit 10 = Σ(digitᵢ × i) mod 11; a remainder of 10 is never issued | 1996. évi XX. törvény, annex 1 |
| TAJ number | Digits in odd positions × 3, in even positions × 7; digit 9 = sum mod 10 | 1996. évi XX. törvény, annex 2 |
| Bank account | Weights 9, 7, 3, 1 repeated. Digits 1–8 form one group; digits 9–16 (or 9–24) form a second group | 35/2017. (XII. 14.) MNB rendelet, annex 1, point 1 |
| IBAN | `HU` + 2 check digits + 24-digit BBAN, ISO 7064 MOD 97-10, check digits 02–98 | 35/2017. MNB rendelet, annex 1, point 2; [MNB](https://www.mnb.hu/letoltes/iban-ellenorzoszam-hu.pdf) |
| Company registration number | Court 01–20; company form code from the official list; no check digit | 21/2006. (V. 18.) IM rendelet, annex 2 |
| Postal code | 4 digits; Budapest 1007 and 1011–1999; other codes 2000–9999 | KSH Helységnévtár; Magyar Posta |

## Limitations

- **No bank name lookup.** The MNB allows its bank code table to be redistributed only
  unchanged. A prefix-to-bank map would be derived data, and prefixes do not identify banks
  one to one: one bank holds about 120 of them. The
  [MNB table](https://www.mnb.hu/penzforgalom/a-hazai-penzforgalmi-infrastruktura/hitelesito-tabla)
  is available from the MNB itself.
- **Postal code → settlement is a lookup, not an existence check.** The data comes from the KSH
  Helységnévtár 2026: 2,873 codes outside Budapest, CC BY 4.0, see [DATA-LICENSE.md](DATA-LICENSE.md).
  It does not contain street-level codes for Budapest, Debrecen, Győr, Miskolc, Pécs and
  Szeged, or PO box codes. `settlements()` therefore returns `[]` for codes it does not know,
  and the `settlement=` option only fails when the data knows the code and the settlement
  differs. Every Budapest code maps to "Budapest".
- **A company registration number has no check digit.** A valid result means the number is
  well-formed, not that the company exists. From 2027 the 2025. évi LIX. törvény introduces a
  unified registration number; a new format will be added once it is published.
- **VAT groups:** the EU VAT number of a VAT group member is built from the group ID, not from
  the member's own tax number. For OTP Bank, `HU17780010` matches `17780010-5-44`, not
  `10537914-4-44`.
- **No online checks.** To check that a taxpayer exists, use NAV's `queryTaxpayer` operation
  in [intragile/laravel-nav-online-szamla](https://github.com/intragile/laravel-nav-online-szamla).
  VIES is not included.

## Testing

```bash
composer test      # PHPUnit
composer analyse   # PHPStan, level max
composer format    # Pint
```

## Security

See [SECURITY.md](SECURITY.md).

## License

The code is MIT licensed, see [LICENSE](LICENSE). The bundled postal code data is CC BY 4.0,
see [DATA-LICENSE.md](DATA-LICENSE.md).

---

Developed and maintained by [IntrAgile Kft.](https://intragile.eu).
