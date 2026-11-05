<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Support;

/**
 * Postal code => settlement names, from the KSH Gazetteer of Hungary (CC BY 4.0, see DATA-LICENSE.md).
 *
 * The data file is loaded on first use and kept for the rest of the process.
 * It holds no Budapest codes.
 *
 * @internal
 */
final class SettlementDirectory
{
    /**
     * @var array<int|string, list<string>>|null
     */
    private static ?array $codes = null;

    /**
     * The settlements of a postal code, or null when the data does not know the code.
     *
     * @return list<string>|null
     */
    public static function settlements(string $code): ?array
    {
        return self::codes()[$code] ?? null;
    }

    /**
     * Whether two settlement names are equal, ignoring case and surrounding or repeated whitespace.
     */
    public static function sameName(string $expected, string $given): bool
    {
        $expected = self::normalise($expected);
        $given = self::normalise($given);

        if ($expected === '' || $given === '') {
            return false;
        }

        // PCRE folds case across Unicode, without requiring ext-mbstring.
        return preg_match('/^'.preg_quote($expected, '/').'\z/iu', $given) === 1;
    }

    /**
     * @return array<int|string, list<string>>
     */
    private static function codes(): array
    {
        if (self::$codes === null) {
            /** @var array<int|string, list<string>> $codes */
            $codes = require dirname(__DIR__, 2).'/resources/postal-codes.php';
            self::$codes = $codes;
        }

        return self::$codes;
    }

    private static function normalise(string $name): string
    {
        // Decomposed input (e.g. from macOS) is composed when ext-intl is available.
        if (class_exists(\Normalizer::class)) {
            $composed = \Normalizer::normalize($name);
            $name = is_string($composed) ? $composed : $name;
        }

        return trim((string) preg_replace('/[\s\x{00A0}\x{202F}]+/u', ' ', $name));
    }
}
