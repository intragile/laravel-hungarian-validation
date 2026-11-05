# Data license

The code of this package is MIT licensed (see [LICENSE](LICENSE)). The postal code data file,
`resources/postal-codes.php`, is **not** covered by the MIT license: it is licensed under the
[Creative Commons Attribution 4.0 International License (CC BY 4.0)](https://creativecommons.org/licenses/by/4.0/).

## Source

- Publisher: Központi Statisztikai Hivatal (KSH, Hungarian Central Statistical Office)
- Dataset: Magyarország helységnévtára 2026 (Gazetteer of Hungary), reference date 2026-01-01
- File: <https://www.ksh.hu/docs/helysegnevtar/hnt_letoltes_2026.xlsx>, sheets
  "Településrészek 2026.01.01." and "Megj. postai ir. számhoz"
- Terms of use: <https://www.ksh.hu/copyright>

## Attribution

Hungarian:

> Forrás: KSH, Magyarország helységnévtára 2026 (www.ksh.hu), CC BY 4.0. A postai
> irányítószámok a Magyar Posta Zrt. adatainak felhasználásával.

English:

> Source: KSH, Gazetteer of Hungary 2026 (www.ksh.hu), CC BY 4.0. The postal codes are based
> on data of Magyar Posta Zrt.

## Modifications

Modified: extracted and restructured. The postal codes of the settlement parts and the codes
named in the postal code notes are merged into a map of postal code to settlement names;
Budapest district names are reduced to "Budapest" and Budapest codes (1xxx) are omitted,
because the library maps every Budapest code to "Budapest" without the data file. The
conversion is reproducible with `php bin/build-postal-codes.php hnt_letoltes_2026.xlsx` from
the source repository.

KSH does not endorse this package or its use of the data.
