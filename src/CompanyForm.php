<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

/**
 * Company form code, digits 3 and 4 of a company registration number.
 *
 * Codes follow 21/2006. (V. 18.) IM rendelet, annex 2
 * (https://net.jogtar.hu/jogszabaly?docid=a0600021.im). Codes 04, 05 and 13
 * are not assigned.
 */
enum CompanyForm: string
{
    case Vallalat = '01';
    case Szovetkezet = '02';
    case Kkt = '03';
    case Bt = '06';
    case Egyesules = '07';
    case KozosVallalat = '08';
    case Kft = '09';
    case Rt = '10';
    case EgyeniCeg = '11';
    case KulfoldiKepviselet = '12';
    case Kht = '14';
    case Erdobirtokossagi = '15';
    case Vizgazdalkodasi = '16';
    case KulfoldiFioktelep = '17';
    case VegrehajtoiIroda = '18';
    case Egtc = '19';
    case EuropaiRt = '20';
    case KozjegyzoiIroda = '21';
    case EgtTelephely = '22';
    case EuropaiSzovetkezet = '23';

    /**
     * The name of the company form.
     *
     * Returns the Hungarian name for "hu" and any "hu_*" or "hu-*" locale, and
     * the English name for every other locale.
     */
    public function label(string $locale = 'en'): string
    {
        $locale = strtolower($locale);

        if ($locale === 'hu' || str_starts_with($locale, 'hu_') || str_starts_with($locale, 'hu-')) {
            return $this->hungarianLabel();
        }

        return $this->englishLabel();
    }

    private function hungarianLabel(): string
    {
        return match ($this) {
            self::Vallalat => 'Vállalat',
            self::Szovetkezet => 'Szövetkezet',
            self::Kkt => 'Közkereseti társaság',
            self::Bt => 'Betéti társaság',
            self::Egyesules => 'Egyesülés',
            self::KozosVallalat => 'Közös vállalat',
            self::Kft => 'Korlátolt felelősségű társaság',
            self::Rt => 'Részvénytársaság',
            self::EgyeniCeg => 'Egyéni cég',
            self::KulfoldiKepviselet => 'Külföldi vállalkozás magyarországi kereskedelmi képviselete',
            self::Kht => 'Közhasznú társaság',
            self::Erdobirtokossagi => 'Erdőbirtokossági társulat',
            self::Vizgazdalkodasi => 'Vízgazdálkodási társulat',
            self::KulfoldiFioktelep => 'Külföldi székhelyű vállalkozás magyarországi fióktelepe',
            self::VegrehajtoiIroda => 'Végrehajtói iroda',
            self::Egtc => 'Európai területi együttműködési csoportosulás',
            self::EuropaiRt => 'Európai részvénytársaság',
            self::KozjegyzoiIroda => 'Közjegyzői iroda',
            self::EgtTelephely => 'Külföldi székhelyű európai gazdasági egyesülés magyarországi telephelye',
            self::EuropaiSzovetkezet => 'Európai szövetkezet',
        };
    }

    private function englishLabel(): string
    {
        return match ($this) {
            self::Vallalat => 'State-owned enterprise',
            self::Szovetkezet => 'Cooperative',
            self::Kkt => 'General partnership',
            self::Bt => 'Limited partnership',
            self::Egyesules => 'Joint venture association',
            self::KozosVallalat => 'Joint enterprise',
            self::Kft => 'Limited liability company',
            self::Rt => 'Company limited by shares',
            self::EgyeniCeg => 'Sole proprietorship',
            self::KulfoldiKepviselet => 'Commercial representative office of a foreign enterprise',
            self::Kht => 'Public benefit company',
            self::Erdobirtokossagi => "Forest owners' association",
            self::Vizgazdalkodasi => 'Water management association',
            self::KulfoldiFioktelep => 'Hungarian branch of a foreign enterprise',
            self::VegrehajtoiIroda => "Bailiff's office",
            self::Egtc => 'European grouping of territorial cooperation',
            self::EuropaiRt => 'European company (SE)',
            self::KozjegyzoiIroda => "Notary's office",
            self::EgtTelephely => 'Hungarian establishment of a foreign European economic interest grouping',
            self::EuropaiSzovetkezet => 'European cooperative society (SCE)',
        };
    }
}
