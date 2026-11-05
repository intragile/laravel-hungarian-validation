<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\Validator;
use IntrAgile\HungarianValidation\CompanyForm;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianBankAccountNumber;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianCompanyRegistrationNumber;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianEuVatNumber;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianIban;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianPostalCode;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianTajNumber;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianTaxIdentificationNumber;
use IntrAgile\HungarianValidation\Laravel\Rules\HungarianTaxNumber;
use IntrAgile\HungarianValidation\Laravel\Rules\IdentifierRule;
use IntrAgile\HungarianValidation\VatCode;
use IntrAgile\HungarianValidation\Violation;
use InvalidArgumentException;
use WeakMap;

/**
 * Registers the string validation rules (`hu_tax_number`, `hu_iban`, ...).
 *
 * Each string rule runs the matching rule object. Laravel resolves one message
 * per string rule, so the extension's default message is a placeholder: the
 * replacer swaps it for the message of the actual failure reason. A message
 * the application defines (inline, `validation.custom.*` or `validation.{rule}`)
 * is never the placeholder and is therefore kept as is.
 */
final class StringRules
{
    /**
     * The failure reasons of the latest run, per validator, rule and attribute.
     *
     * @var WeakMap<Validator, array<string, array<string, Violation>>>
     */
    private WeakMap $reasons;

    private function __construct()
    {
        $this->reasons = new WeakMap;
    }

    /**
     * The registered rule names and the rule class each one runs.
     *
     * @return array<non-empty-string, class-string<IdentifierRule>>
     */
    public static function rules(): array
    {
        return [
            'hu_tax_number' => HungarianTaxNumber::class,
            'hu_eu_vat_number' => HungarianEuVatNumber::class,
            'hu_tax_id' => HungarianTaxIdentificationNumber::class,
            'hu_taj' => HungarianTajNumber::class,
            'hu_bank_account' => HungarianBankAccountNumber::class,
            'hu_iban' => HungarianIban::class,
            'hu_company_number' => HungarianCompanyRegistrationNumber::class,
            'hu_postal_code' => HungarianPostalCode::class,
        ];
    }

    public static function register(Factory $factory): void
    {
        $registry = new self;

        foreach (self::rules() as $name => $class) {
            $factory->extend($name, $registry->extension($name), self::placeholder($name));
            $factory->replacer($name, $registry->replacer($name, $class));
        }
    }

    /**
     * Builds the rule object a string rule and its parameters describe.
     *
     * @param  list<string>  $parameters
     *
     * @throws InvalidArgumentException When a parameter is unknown or invalid.
     */
    public static function make(string $name, array $parameters = []): IdentifierRule
    {
        return match ($name) {
            'hu_tax_number' => new HungarianTaxNumber(array_map(
                static fn (string $code): VatCode => VatCode::tryFrom(self::integer($name, 'vat_code', $code))
                    ?? throw new InvalidArgumentException(sprintf('Unknown VAT code "%s" for validation rule %s.', $code, $name)),
                self::options($name, $parameters, ['vat_code'])['vat_code'] ?? [],
            )),
            'hu_eu_vat_number' => new HungarianEuVatNumber(
                self::single($name, 'matches', self::options($name, $parameters, ['matches'])),
            ),
            'hu_tax_id' => new HungarianTaxIdentificationNumber(
                self::single($name, 'birth_date', self::options($name, $parameters, ['birth_date'])),
            ),
            'hu_taj' => self::withoutOptions(new HungarianTajNumber, $name, $parameters),
            'hu_bank_account' => new HungarianBankAccountNumber(self::lengthOption($name, $parameters)),
            'hu_iban' => self::withoutOptions(new HungarianIban, $name, $parameters),
            'hu_company_number' => new HungarianCompanyRegistrationNumber(array_map(
                static fn (string $form): CompanyForm => CompanyForm::tryFrom($form)
                    ?? throw new InvalidArgumentException(sprintf('Unknown company form "%s" for validation rule %s.', $form, $name)),
                self::options($name, $parameters, ['form'])['form'] ?? [],
            )),
            'hu_postal_code' => new HungarianPostalCode(
                self::flag($name, 'budapest', self::options($name, $parameters, ['budapest'])),
            ),
            default => throw new InvalidArgumentException(sprintf('Unknown validation rule: %s.', $name)),
        };
    }

    /**
     * The default message of a string rule, replaced by the reason-specific one.
     */
    public static function placeholder(string $name): string
    {
        return IdentifierRule::TRANSLATION_NAMESPACE.'::'.$name;
    }

    /**
     * @return Closure(string, mixed, array<array-key, mixed>, Validator): bool
     */
    private function extension(string $name): Closure
    {
        return function (string $attribute, mixed $value, array $parameters, Validator $validator) use ($name): bool {
            $rule = self::make($name, self::strings($name, $parameters));

            if ($rule instanceof DataAwareRule) {
                $rule->setData($validator->getData());
            }

            $violation = $rule->violationFor($value);
            $reasons = $this->reasons[$validator] ?? [];

            if ($violation === null) {
                unset($reasons[$name][$attribute]);
            } else {
                $reasons[$name][$attribute] = $violation;
            }

            $this->reasons[$validator] = $reasons;

            return $violation === null;
        };
    }

    /**
     * @param  class-string<IdentifierRule>  $class
     * @return Closure(string, string, string, array<array-key, mixed>, Validator): string
     */
    private function replacer(string $name, string $class): Closure
    {
        return function (string $message, string $attribute, string $rule, array $parameters, Validator $validator) use ($name, $class): string {
            if ($message !== self::placeholder($name)) {
                return $message;
            }

            $violation = $this->reasons[$validator][$name][$attribute] ?? Violation::Format;
            $translated = $validator->getTranslator()->get($class::messageKey($violation));

            if (! is_string($translated)) {
                return $message;
            }

            // The replacer runs again on the translated message, which is no longer the placeholder.
            return $validator->makeReplacements($translated, $attribute, $rule, $parameters);
        };
    }

    /**
     * Parses `key=value` and bare `flag` parameters.
     *
     * @param  list<string>  $parameters
     * @param  list<string>  $allowed
     * @return array<string, non-empty-list<string>>
     *
     * @throws InvalidArgumentException When a parameter is not allowed.
     */
    private static function options(string $name, array $parameters, array $allowed): array
    {
        $options = [];

        foreach ($parameters as $parameter) {
            [$key, $value] = array_pad(explode('=', trim($parameter), 2), 2, '');
            $key = trim($key);

            if (! in_array($key, $allowed, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown parameter "%s" for validation rule %s; allowed: %s.',
                    $parameter,
                    $name,
                    $allowed === [] ? 'none' : implode(', ', $allowed),
                ));
            }

            $options[$key][] = trim($value);
        }

        return $options;
    }

    /**
     * @param  array<string, non-empty-list<string>>  $options
     *
     * @throws InvalidArgumentException When the option is repeated or empty.
     */
    private static function single(string $name, string $key, array $options): ?string
    {
        if (! isset($options[$key])) {
            return null;
        }

        if (count($options[$key]) > 1 || $options[$key][0] === '') {
            throw new InvalidArgumentException(sprintf('Validation rule %s expects exactly one non-empty "%s" value.', $name, $key));
        }

        return $options[$key][0];
    }

    /**
     * @param  array<string, non-empty-list<string>>  $options
     *
     * @throws InvalidArgumentException When the flag has a value.
     */
    private static function flag(string $name, string $key, array $options): bool
    {
        if (! isset($options[$key])) {
            return false;
        }

        if ($options[$key] !== ['']) {
            throw new InvalidArgumentException(sprintf('Validation rule %s takes "%s" as a flag without a value.', $name, $key));
        }

        return true;
    }

    /**
     * @param  list<string>  $parameters
     */
    private static function lengthOption(string $name, array $parameters): ?int
    {
        $length = self::single($name, 'length', self::options($name, $parameters, ['length']));

        return $length === null ? null : self::integer($name, 'length', $length);
    }

    /**
     * @throws InvalidArgumentException When the value is not a decimal integer.
     */
    private static function integer(string $name, string $key, string $value): int
    {
        if (preg_match('/^[0-9]+\z/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('Validation rule %s expects an integer "%s" value, "%s" given.', $name, $key, $value));
        }

        return (int) $value;
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     * @return list<string>
     *
     * @throws InvalidArgumentException When a parameter is not a string.
     */
    private static function strings(string $name, array $parameters): array
    {
        $strings = [];

        foreach ($parameters as $parameter) {
            if (! is_string($parameter)) {
                throw new InvalidArgumentException(sprintf('Validation rule %s expects string parameters.', $name));
            }

            $strings[] = $parameter;
        }

        return $strings;
    }

    /**
     * @param  list<string>  $parameters
     */
    private static function withoutOptions(IdentifierRule $rule, string $name, array $parameters): IdentifierRule
    {
        self::options($name, $parameters, []);

        return $rule;
    }
}
