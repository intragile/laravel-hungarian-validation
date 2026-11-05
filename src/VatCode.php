<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

/**
 * The VAT code: the 9th digit of a Hungarian tax number.
 *
 * Allowed values are 1 to 5, as defined by VatCodeType in the NAV Online
 * Számla schema (common.xsd).
 */
enum VatCode: int
{
    /** Subject-exempt taxpayer, or one carrying out only exempt activities. */
    case Exempt = 1;

    /** Taxpayer under the general VAT rules. */
    case General = 2;

    /**
     * Taxpayer under the simplified entrepreneurial tax (EVA).
     *
     * EVA has been abolished, but the code remains valid in existing tax numbers.
     */
    case SimplifiedEntrepreneurialTax = 3;

    /** Member of a VAT group. */
    case GroupMember = 4;

    /** Identifier of a VAT group itself. */
    case Group = 5;

    /**
     * A short description in Hungarian ("hu") or English (any other locale).
     */
    public function label(string $locale = 'en'): string
    {
        if ($locale === 'hu') {
            return match ($this) {
                self::Exempt => 'alanyi adómentes',
                self::General => 'általános szabályok szerint adózó',
                self::SimplifiedEntrepreneurialTax => 'egyszerűsített vállalkozói adó (EVA)',
                self::GroupMember => 'csoporttag',
                self::Group => 'csoportazonosító',
            };
        }

        return match ($this) {
            self::Exempt => 'subject-exempt',
            self::General => 'general rules',
            self::SimplifiedEntrepreneurialTax => 'simplified entrepreneurial tax',
            self::GroupMember => 'VAT group member',
            self::Group => 'VAT group identifier',
        };
    }
}
