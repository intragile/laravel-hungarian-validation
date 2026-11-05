# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project adheres to
[Semantic Versioning](https://semver.org/).

## [1.0.1] - 2026-11-05

### Changed

- The package homepage and the documentation link to https://intragile.eu.

## [1.0.0] - 2026-11-05

First public release.

### Added

- Value objects with `parse()`, `tryParse()`, `isValid()`, `validate()` and `format()` for the
  Hungarian tax number, EU VAT number, personal tax identification number, TAJ number, domestic
  bank account number, Hungarian IBAN, company registration number and postal code.
- Check digits verified against the law and the MNB/NAV specifications, together with VAT code,
  territorial code, embedded birth date, court and company form checks.
- Tolerant input normalisation (spaces, hyphens, Unicode dashes, dots, slashes, `Cg.` prefix).
- Laravel rule objects and string rules with options, and reason-specific messages in English
  and Hungarian.
- Postal code to settlement lookup from the KSH Helységnévtár 2026 (CC BY 4.0).
- Generators and a Faker provider for valid test data.

[1.0.1]: https://github.com/intragile/laravel-hungarian-validation/releases/tag/v1.0.1
[1.0.0]: https://github.com/intragile/laravel-hungarian-validation/releases/tag/v1.0.0
