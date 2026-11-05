<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Support;

/**
 * @internal
 */
final class Normalizer
{
    /**
     * Matches whitespace (including no-break spaces), hyphens, Unicode dashes,
     * the minus sign, dots and slashes, which users paste from documents.
     */
    private const string SEPARATORS = '/[\s\x{00A0}\x{202F}\x{2010}-\x{2015}\x{2212}\-.\/]+/u';

    /**
     * Removes every separator character, wherever it appears.
     */
    public static function compact(string $value): string
    {
        return preg_replace(self::SEPARATORS, '', $value) ?? $value;
    }
}
