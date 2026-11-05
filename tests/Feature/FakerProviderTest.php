<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Feature;

use Closure;
use DateTimeImmutable;
use Faker\Factory;
use Faker\Generator;
use IntrAgile\HungarianValidation\BankAccountNumber;
use IntrAgile\HungarianValidation\CompanyForm;
use IntrAgile\HungarianValidation\CompanyRegistrationNumber;
use IntrAgile\HungarianValidation\EuVatNumber;
use IntrAgile\HungarianValidation\Faker\HungarianIdentifierProvider;
use IntrAgile\HungarianValidation\Iban;
use IntrAgile\HungarianValidation\PostalCode;
use IntrAgile\HungarianValidation\TajNumber;
use IntrAgile\HungarianValidation\TaxIdentificationNumber;
use IntrAgile\HungarianValidation\TaxNumber;
use IntrAgile\HungarianValidation\Tests\TestCase;
use IntrAgile\HungarianValidation\VatCode;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

final class FakerProviderTest extends TestCase
{
    private const int ITERATIONS = 200;

    private static function provider(): HungarianIdentifierProvider
    {
        return new HungarianIdentifierProvider(Factory::create());
    }

    /**
     * @return iterable<string, array{Closure(HungarianIdentifierProvider): string, Closure(string): bool}>
     */
    public static function formatters(): iterable
    {
        yield 'tax number' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huTaxNumber(),
            static fn (string $v): bool => TaxNumber::isValid($v) && TaxNumber::parse($v)->format() === $v,
        ];
        yield 'tax number with VAT code' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huTaxNumber(4),
            static fn (string $v): bool => TaxNumber::tryParse($v)?->vatCode() === VatCode::GroupMember,
        ];
        yield 'EU VAT number' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huEuVatNumber(),
            static fn (string $v): bool => EuVatNumber::isValid($v) && EuVatNumber::parse($v)->format() === $v,
        ];
        yield 'tax ID' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huTaxId(),
            static fn (string $v): bool => TaxIdentificationNumber::isValid($v) && TaxIdentificationNumber::parse($v)->format() === $v,
        ];
        yield 'tax ID with birth date' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huTaxId(new DateTimeImmutable('1980-01-01')),
            static fn (string $v): bool => TaxIdentificationNumber::tryParse($v)?->birthDate()->format('Y-m-d') === '1980-01-01',
        ];
        yield 'TAJ number' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huTajNumber(),
            static fn (string $v): bool => TajNumber::isValid($v) && TajNumber::parse($v)->format() === $v,
        ];
        yield 'bank account' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huBankAccountNumber(),
            static fn (string $v): bool => BankAccountNumber::tryParse($v)?->format() === $v && BankAccountNumber::parse($v)->length() === 16,
        ];
        yield 'bank account 24 digits unformatted' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huBankAccountNumber(24, formatted: false),
            static fn (string $v): bool => BankAccountNumber::tryParse($v)?->value() === $v && strlen($v) === 24,
        ];
        yield 'IBAN' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huIban(),
            static fn (string $v): bool => Iban::isValid($v) && Iban::parse($v)->format() === $v,
        ];
        yield 'company registration number' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huCompanyRegistrationNumber(),
            static fn (string $v): bool => CompanyRegistrationNumber::tryParse($v)?->format() === $v,
        ];
        yield 'company registration number with form' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huCompanyRegistrationNumber('09'),
            static fn (string $v): bool => CompanyRegistrationNumber::tryParse($v)?->form() === CompanyForm::Kft,
        ];
        yield 'postal code' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huPostalCode(),
            static fn (string $v): bool => PostalCode::isValid($v),
        ];
        yield 'Budapest postal code' => [
            static fn (HungarianIdentifierProvider $p): string => $p->huPostalCode(budapest: true),
            static fn (string $v): bool => PostalCode::tryParse($v)?->isBudapest() === true,
        ];
    }

    /**
     * @param  Closure(HungarianIdentifierProvider): string  $generate
     * @param  Closure(string): bool  $isValid
     */
    #[DataProvider('formatters')]
    public function test_every_value_is_valid(Closure $generate, Closure $isValid): void
    {
        $provider = self::provider();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $value = $generate($provider);
            self::assertTrue($isValid($value), $value);
        }
    }

    public function test_seed_is_reproducible(): void
    {
        $run = static function (): array {
            $faker = Factory::create();
            $faker->seed(1);
            $provider = new HungarianIdentifierProvider($faker);

            $values = [];

            for ($i = 0; $i < 20; $i++) {
                $values[] = [
                    $provider->huTaxNumber(), $provider->huEuVatNumber(), $provider->huTaxId(),
                    $provider->huTajNumber(), $provider->huBankAccountNumber(), $provider->huIban(),
                    $provider->huCompanyRegistrationNumber(), $provider->huPostalCode(),
                ];
            }

            return $values;
        };

        $first = $run();

        self::assertSame($first, $run());
        self::assertGreaterThan(1, count(array_unique(array_column($first, 0))));
    }

    public function test_different_seeds_differ(): void
    {
        $faker = Factory::create();
        $provider = new HungarianIdentifierProvider($faker);

        $faker->seed(1);
        $first = $provider->huIban();
        $faker->seed(2);

        self::assertNotSame($first, $provider->huIban());
    }

    /**
     * Generator::__call() forwards fake()->huTaxNumber() to format('huTaxNumber');
     * format() is called directly so the dynamic call stays statically typed.
     */
    public function test_laravel_fake_helper(): void
    {
        $generators = [fake(), fake('hu_HU'), $this->app?->make(Generator::class)];

        foreach ($generators as $faker) {
            self::assertInstanceOf(Generator::class, $faker);

            $taxNumber = $faker->format('huTaxNumber');
            self::assertIsString($taxNumber);
            self::assertTrue(TaxNumber::isValid($taxNumber), $taxNumber);
        }
    }

    public function test_formatter_accepts_arguments_through_the_generator(): void
    {
        $value = fake()->format('huTaxNumber', [2]);

        self::assertIsString($value);
        self::assertSame(VatCode::General, TaxNumber::parse($value)->vatCode());
    }

    public function test_unknown_vat_code_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::provider()->huTaxNumber(9);
    }

    public function test_unknown_company_form_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::provider()->huCompanyRegistrationNumber('99');
    }

    public function test_provider_is_added_once_per_generator(): void
    {
        $first = $this->app?->make(Generator::class, ['locale' => 'hu_HU']);
        $this->app?->make(Generator::class, ['locale' => 'hu_HU']);

        // Laravel caches generators per locale for the process, across application instances.
        $this->refreshApplication();
        $this->app?->make(Generator::class, ['locale' => 'hu_HU']);

        self::assertInstanceOf(Generator::class, $first);
        $providers = $first->getProviders();
        self::assertIsArray($providers);
        $providers = array_filter($providers, static fn (mixed $provider): bool => $provider instanceof HungarianIdentifierProvider);
        self::assertCount(1, $providers);
    }
}
