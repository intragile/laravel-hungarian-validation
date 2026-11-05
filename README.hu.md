# Laravel Hungarian Validation

Fejlesztő és karbantartó: [IntrAgile Kft.](https://intragile.eu)

[![Tests](https://github.com/intragile/laravel-hungarian-validation/actions/workflows/tests.yml/badge.svg)](https://github.com/intragile/laravel-hungarian-validation/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/intragile/laravel-hungarian-validation.svg)](https://packagist.org/packages/intragile/laravel-hungarian-validation)
[![PHP](https://img.shields.io/packagist/php-v/intragile/laravel-hungarian-validation.svg)](https://packagist.org/packages/intragile/laravel-hungarian-validation)
[![License](https://img.shields.io/packagist/l/intragile/laravel-hungarian-validation.svg)](LICENSE)

Magyar azonosítók validálása, normalizálása, formázása és tesztadat-generálása. A csomag a
következő azonosítókat kezeli: adószám, közösségi adószám, adóazonosító jel, TAJ-szám, belföldi
bankszámlaszám, magyar IBAN, cégjegyzékszám és irányítószám.

[English documentation → README.md](README.md)

- **Valódi ellenőrzés, nem regex.** Minden ellenőrzőjegyes azonosítót a jogszabályban vagy az
  MNB/NAV leírásban szereplő algoritmus szerint ellenőriz. Emellett kódlistákat és belső
  összefüggéseket is vizsgál: áfakód, területi kód, beágyazott születési dátum, cégbíróság és
  cégforma. A szabályok forrása lent szerepel.
- **Normalizálás, formázás és felbontás.** A `10773381244`, a `10773381 2 44` és a PDF-ből
  másolt, nagykötőjeles `10773381–2–44` egyaránt `10773381-2-44` lesz. A value objectek a
  részeket is visszaadják: törzsszám, áfakód, területi kód.
- **Keretrendszer-független mag.** A mag függőség nélküli, tiszta PHP. A Laravel-réteg rule
  objektumokat, string rule-okat, az okra szabott magyar és angol hibaüzeneteket, valamint egy
  Faker-providert ad hozzá.
- **Érvényes tesztadat.** A generátorok és a Faker-provider érvényes azonosítókat adnak
  seederekhez és tesztekhez.

## Miért ez a csomag?

A magyar azonosítókra írt PHP-csomagok többsége csak regexszel ellenőriz. Ezért elfogadják a
0-s áfakódú adószámot, a törvény szerint kiadhatatlan ellenőrzőjegyű adóazonosító jelet és a
nem létező formakódú cégjegyzékszámot. Van olyan csomag is, amely hibásan számolja az adószám
ellenőrzőjegyét, így valódi adószámokat utasít el. Az IBAN-t a benne lévő GIRO-ellenőrzéssel
együtt egyik sem kezeli. Azt a szabályt sem, hogy a 24 jegyű számla második ellenőrzőjegye 16
jegyet fed le. Ebben a csomagban mindegyik esetre regressziós teszt van.

## Követelmények

- PHP 8.3+
- Laravel 12 vagy 13 a validációs szabályokhoz (opcionális)
- `fakerphp/faker` 1.23+ a Faker-providerhez (opcionális)

## Telepítés

```bash
composer require intragile/laravel-hungarian-validation
```

A ServiceProvider automatikusan betöltődik. A hibaüzenetek testreszabásához:

```bash
php artisan vendor:publish --tag=hungarian-validation-lang
```

## A mag használata (bármilyen PHP-projektben)

Minden azonosító osztálynak ugyanaz az API-ja:

```php
use IntrAgile\HungarianValidation\TaxNumber;
use IntrAgile\HungarianValidation\Violation;

TaxNumber::isValid('10773381 2 44');            // true
TaxNumber::validate('10773380-2-44');           // Violation::Checksum
TaxNumber::tryParse('értelmetlen');             // null

$taxNumber = TaxNumber::parse('10773381244');   // érvénytelen bemenetre InvalidIdentifierException
$taxNumber->format();                           // "10773381-2-44"
$taxNumber->value();                            // "10773381244"
$taxNumber->baseNumber();                       // "10773381"
$taxNumber->vatCode();                          // VatCode::General
$taxNumber->vatCode()->label('hu');             // "általános szabályok szerint adózó"
$taxNumber->regionCode();                       // "44"
$taxNumber->euVatNumber()->format();            // "HU10773381"
```

A `validate()` az első megsértett szabályt adja vissza `Violation`-ként (`Format`, `Checksum`,
`VatCode`, `Region`, `BirthDate`, `BirthDateMismatch`, `TaxNumberMismatch`, `Court`,
`CompanyForm`, `Range`, `Length`, `NotAllowed`, `SettlementMismatch`). Érvényes értéknél
`null`-t ad. A `parse()` `InvalidIdentifierException`-t dob, amely tartalmazza a hiba okát.

Az elválasztó karaktereket bárhol figyelmen kívül hagyja. Ilyenek: a szóköz (a nem törő
szóköz is), a kötőjel, a Unicode-gondolatjelek, a mínuszjel, a pont és a perjel.

### Azonosítók

| Osztály | Példa (`format()`) | Ellenőrzés | Részek |
|---|---|---|---|
| `TaxNumber` (adószám) | `10773381-2-44` | a törzsszám ellenőrzőjegye, áfakód 1–5, területi kód | `baseNumber()`, `vatCode()`, `regionCode()`, `euVatNumber()` |
| `EuVatNumber` (közösségi adószám) | `HU17780010` | `HU` előtag, ellenőrzőjegy | `baseNumber()`, `matches(TaxNumber)` |
| `TaxIdentificationNumber` (adóazonosító jel) | `8412721233` | 8-as kezdet, ellenőrzőjegy, a beágyazott születési dátum nem jövőbeli | `birthDate()`, `serial()`, `matchesBirthDate()` |
| `TajNumber` | `123 456 788` | ellenőrzőjegy | |
| `BankAccountNumber` | `11773016-11111018` | GIRO-ellenőrzőjegyek, 16 vagy 24 jegy | `bankCode()`, `branchCode()`, `length()`, `toIban()` |
| `Iban` | `HU42 1177 3016 1111 1018 0000 0000` | `HU`, 28 karakter, MOD 97-10 és a belső GIRO-ellenőrzés | `bankAccount()`, `fromBankAccount()` |
| `CompanyRegistrationNumber` (cégjegyzékszám) | `01-10-041683` | cégbíróság, cégforma | `court()`, `form()`, `serial()` |
| `PostalCode` (irányítószám) | `1052` | szerkezet és a kiadott tartományok | `isBudapest()`, `budapestDistrict()`, `settlements()`, `matchesSettlement()` |

```php
TaxIdentificationNumber::validate('8412721233', new DateTimeImmutable('1980-01-01')); // null
EuVatNumber::parse('HU17780010')->matches(TaxNumber::parse('17780010-5-44'));        // true
BankAccountNumber::parse('11773016-11111018')->toIban()->format();                   // "HU42 1177 3016 ..."
CompanyRegistrationNumber::parse('Cg. 01-10-041683')->form();                        // CompanyForm::Rt
PostalCode::parse('1052')->budapestDistrict();                                        // 5
PostalCode::parse('7461')->settlements();                                             // ["Kaposvár"]
```

## Laravel-validáció

Minden azonosítóhoz van rule objektum és string rule is:

| Rule objektum | String rule | Opciók |
|---|---|---|
| `new HungarianTaxNumber(vatCode: [2, 4])` | `hu_tax_number:vat_code=2,vat_code=4` | engedélyezett áfakódok |
| `new HungarianEuVatNumber('tax_number')` | `hu_eu_vat_number:matches=tax_number` | egyezzen egy másik mezőben megadott adószámmal |
| `new HungarianTaxIdentificationNumber('born_on')` | `hu_tax_id:birth_date=born_on` | egyezzen egy másik mezőben megadott születési dátummal; fix dátum a `birthDate:` paraméterrel adható meg |
| `new HungarianTajNumber` | `hu_taj` | |
| `new HungarianBankAccountNumber(16)` | `hu_bank_account:length=16` | csak 16 vagy csak 24 jegy |
| `new HungarianIban` | `hu_iban` | |
| `new HungarianCompanyRegistrationNumber(CompanyForm::Kft)` | `hu_company_number:form=09` | engedélyezett cégformák |
| `new HungarianPostalCode(budapestOnly: true, settlementField: 'city')` | `hu_postal_code:budapest,settlement=city` | csak budapesti kód; tartozzon a másik mezőben megadott településhez |

A rule osztályok az `IntrAgile\HungarianValidation\Laravel\Rules` névtérben vannak.

```php
$request->validate([
    'tax_number' => ['required', 'hu_tax_number:vat_code=2'],
    'eu_vat' => ['nullable', 'hu_eu_vat_number:matches=tax_number'],
    'people.*.tax_id' => ['required', 'hu_tax_id:birth_date=people.*.born_on'],
    'iban' => ['required', new HungarianIban],
]);
```

- **Okra szabott hibaüzenetek:** a rossz formátum, a hibás ellenőrzőjegy és az ismeretlen
  területi kód külön üzenetet kap, magyarul és angolul. A saját üzeneteid (inline,
  `validation.custom.*` vagy `validation.hu_tax_number`) mindig elsőbbséget kapnak.
- **Mezőhivatkozások:** a pontozott jelölés és a wildcard (`people.*.born_on`) is működik.
- **Születési dátum:** elfogadott `DateTimeInterface`, ISO-dátum vagy magyar írásmód
  (`1980.01.01.`, `1980. 01. 01.`). Csak a naptári nap számít.
- **Bemenet típusa:** string és nemnegatív egész szám. Az egész számot nem egészíti ki
  nullákkal, ezért a nullával kezdődő TAJ-számot stringként kell küldeni.
- **Hibás rule-opciók:** ismeretlen áfakód vagy ismeretlen paraméter esetén
  `InvalidArgumentException` keletkezik, mert ezek programozási hibák.

## Tesztadat

```php
use IntrAgile\HungarianValidation\Generators\TaxNumberGenerator;
use IntrAgile\HungarianValidation\VatCode;

(new TaxNumberGenerator)->generate(VatCode::General, '13')->format();  // pl. "24837162-2-13"
```

Minden azonosítónak van generátora az `IntrAgile\HungarianValidation\Generators` névtérben.
Seedelt `Random\Randomizer`-rel reprodukálható kimenetet ad. Laravelben a Faker-provider
automatikusan regisztrálódik:

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

Laravel nélkül: `$faker->addProvider(new HungarianIdentifierProvider($faker));`.

## Szabályok és források

| Azonosító | Szabály | Forrás |
|---|---|---|
| Adószám | Az 1–7. jegy súlya 9, 7, 3, 1, 9, 7, 3; a 8. jegy (10 − összeg mod 10) mod 10. Áfakód 1–5. Területi kód 02–20, 22–44 vagy 51. | [Céginformációs Szolgálat](https://ceginformaciosszolgalat.kormany.hu/download/4/27/02000/adoszam_adoazonosito_ellenorzes_2018.pdf); NAV Online Számla `common.xsd`; NAV tájékoztató az áfaalanyok adatairól (*afaalany_taj.html*, archív, 2008–2021) |
| Közösségi adószám | `HU` + a 8 jegyű törzsszám, ugyanazzal az ellenőrzőjeggyel | mint fent |
| Adóazonosító jel | 1. jegy = 8; 2–6. jegy = az 1867-01-01 óta eltelt napok; 10. jegy = Σ(jegyᵢ × i) mod 11; 10-es maradékú számot nem adnak ki | 1996. évi XX. törvény, 1. számú melléklet |
| TAJ-szám | A páratlan helyen álló jegyek × 3, a páros helyen állók × 7; 9. jegy = összeg mod 10 | 1996. évi XX. törvény, 2. számú melléklet |
| Bankszámlaszám | Súlyok 9, 7, 3, 1 ismétlődve. Az 1–8. jegy az egyik csoport, a 9–16. (vagy 9–24.) jegy a másik. | 35/2017. (XII. 14.) MNB rendelet, 1. melléklet 1. pont |
| IBAN | `HU` + 2 ellenőrző jegy + 24 jegyű BBAN, ISO 7064 MOD 97-10, ellenőrző jegyek 02–98 | 35/2017. MNB rendelet, 1. melléklet 2. pont; [MNB](https://www.mnb.hu/letoltes/iban-ellenorzoszam-hu.pdf) |
| Cégjegyzékszám | Cégbíróság 01–20; cégformakód a hivatalos listából; ellenőrzőjegy nincs | 21/2006. (V. 18.) IM rendelet, 2. számú melléklet |
| Irányítószám | 4 jegy; Budapest 1007 és 1011–1999, a többi 2000–9999 | KSH Helységnévtár; Magyar Posta |

## Korlátok

- **Nincs banknév-lekérdezés.** Az MNB az irányítókód-táblát csak változatlan tartalommal
  engedi terjeszteni. Egy prefix → bank térkép már származtatott adat lenne. A prefixek ráadásul
  nem azonosítanak egyértelműen egy bankot: egy bankhoz kb. 120 prefix tartozik. Az
  [MNB táblája](https://www.mnb.hu/penzforgalom/a-hazai-penzforgalmi-infrastruktura/hitelesito-tabla)
  az MNB oldalán érhető el.
- **Az irányítószám → település csak lekérdezés, nem létezés-ellenőrzés.** Az adat a KSH
  Helységnévtár 2026-os kiadásából származik: 2873 kód Budapesten kívül, CC BY 4.0 licenccel, lásd
  [DATA-LICENSE.md](DATA-LICENSE.md). Nincsenek benne Budapest, Debrecen, Győr, Miskolc, Pécs és
  Szeged utcaszintű kódjai, sem a postafiókkódok. Ezért a `settlements()` ismeretlen kódra
  `[]`-t ad. A `settlement=` opció csak akkor bukik, ha az adat ismeri a kódot, és a település
  nem egyezik. Minden budapesti kód „Budapest”-et ad.
- **A cégjegyzékszámnak nincs ellenőrzőjegye.** Az érvényes eredmény annyit jelent, hogy a szám
  formailag helyes, azt nem, hogy a cég létezik. A 2025. évi LIX. törvény 2027-től egységes
  nyilvántartási számot vezet be. Az új formátum a kihirdetése után kerül be.
- **ÁFA-csoportok:** egy csoporttag közösségi adószáma a csoportazonosítóból képződik, nem a
  tag saját adószámából. Az OTP Banknál például a `HU17780010` a `17780010-5-44`-gyel egyezik, nem
  a `10537914-4-44`-gyel.
- **Nincs online ellenőrzés.** Annak ellenőrzésére, hogy egy adózó létezik-e, használd a NAV
  `queryTaxpayer` műveletét az
  [intragile/laravel-nav-online-szamla](https://github.com/intragile/laravel-nav-online-szamla)
  csomagban. VIES-ellenőrzést a csomag nem végez.

## Tesztelés

```bash
composer test      # PHPUnit
composer analyse   # PHPStan, max szint
composer format    # Pint
```

## Biztonság

Lásd [SECURITY.md](SECURITY.md).

## Licenc

A kód MIT licencű, lásd [LICENSE](LICENSE). A csomagban lévő irányítószám-adat CC BY 4.0
licencű, lásd [DATA-LICENSE.md](DATA-LICENSE.md).

---

Fejleszti és karbantartja az [IntrAgile Kft.](https://intragile.eu)
