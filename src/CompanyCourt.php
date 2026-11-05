<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

/**
 * Court of registration, the first two digits of a company registration number.
 *
 * Case names are the county (vármegye) each court of registration serves;
 * code 01 is the capital.
 */
enum CompanyCourt: string
{
    case Budapest = '01';
    case Baranya = '02';
    case BacsKiskun = '03';
    case Bekes = '04';
    case BorsodAbaujZemplen = '05';
    case CsongradCsanad = '06';
    case Fejer = '07';
    case GyorMosonSopron = '08';
    case HajduBihar = '09';
    case Heves = '10';
    case KomaromEsztergom = '11';
    case Nograd = '12';
    case Pest = '13';
    case Somogy = '14';
    case SzabolcsSzatmarBereg = '15';
    case JaszNagykunSzolnok = '16';
    case Tolna = '17';
    case Vas = '18';
    case Veszprem = '19';
    case Zala = '20';

    /**
     * The official Hungarian name of the court of registration.
     */
    public function label(): string
    {
        return match ($this) {
            self::Budapest => 'Fővárosi Törvényszék Cégbírósága',
            self::Baranya => 'Pécsi Törvényszék Cégbírósága',
            self::BacsKiskun => 'Kecskeméti Törvényszék Cégbírósága',
            self::Bekes => 'Gyulai Törvényszék Cégbírósága',
            self::BorsodAbaujZemplen => 'Miskolci Törvényszék Cégbírósága',
            self::CsongradCsanad => 'Szegedi Törvényszék Cégbírósága',
            self::Fejer => 'Székesfehérvári Törvényszék Cégbírósága',
            self::GyorMosonSopron => 'Győri Törvényszék Cégbírósága',
            self::HajduBihar => 'Debreceni Törvényszék Cégbírósága',
            self::Heves => 'Egri Törvényszék Cégbírósága',
            self::KomaromEsztergom => 'Tatabányai Törvényszék Cégbírósága',
            self::Nograd => 'Balassagyarmati Törvényszék Cégbírósága',
            self::Pest => 'Budapest Környéki Törvényszék Cégbírósága (Pest vármegye)',
            self::Somogy => 'Kaposvári Törvényszék Cégbírósága',
            self::SzabolcsSzatmarBereg => 'Nyíregyházi Törvényszék Cégbírósága',
            self::JaszNagykunSzolnok => 'Szolnoki Törvényszék Cégbírósága',
            self::Tolna => 'Szekszárdi Törvényszék Cégbírósága',
            self::Vas => 'Szombathelyi Törvényszék Cégbírósága',
            self::Veszprem => 'Veszprémi Törvényszék Cégbírósága',
            self::Zala => 'Zalaegerszegi Törvényszék Cégbírósága',
        };
    }
}
