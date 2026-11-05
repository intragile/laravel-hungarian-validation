<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

/**
 * The reason an identifier failed validation.
 *
 * The backing value is the last segment of the translation key
 * (`hungarian-validation::validation.{type}.{violation}`).
 */
enum Violation: string
{
    case Format = 'format';
    case Checksum = 'checksum';
    case VatCode = 'vat_code';
    case Region = 'region';
    case BirthDate = 'birth_date';
    case BirthDateMismatch = 'birth_date_mismatch';
    case TaxNumberMismatch = 'tax_number_mismatch';
    case Court = 'court';
    case CompanyForm = 'company_form';
    case Range = 'range';
    case Length = 'length';
    case NotAllowed = 'not_allowed';

    /**
     * A short English description, used in exception messages.
     */
    public function description(): string
    {
        return match ($this) {
            self::Format => 'invalid format',
            self::Checksum => 'checksum mismatch',
            self::VatCode => 'unknown VAT code',
            self::Region => 'unknown territorial code',
            self::BirthDate => 'invalid embedded birth date',
            self::BirthDateMismatch => 'birth date does not match',
            self::TaxNumberMismatch => 'tax number does not match',
            self::Court => 'unknown court of registration',
            self::CompanyForm => 'unknown company form',
            self::Range => 'value out of range',
            self::Length => 'invalid length',
            self::NotAllowed => 'value not allowed',
        };
    }
}
