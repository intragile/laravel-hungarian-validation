<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit\Support;

use IntrAgile\HungarianValidation\Support\Normalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NormalizerTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function separatorVariants(): iterable
    {
        yield 'hyphen' => ['12345678-2-42'];
        yield 'en dash and no-break space' => [" 12345678\u{2013}2\u{00A0}42. "];
        yield 'slash' => ['12345678/2/42'];
        yield 'minus sign' => ["12345678\u{2212}2\u{2212}42"];
        yield 'narrow no-break space' => ["12345678\u{202F}2\u{202F}42"];
        yield 'em dash and tab' => ["12345678\u{2014}2\t42"];
    }

    #[DataProvider('separatorVariants')]
    public function test_strips_all_separator_variants(string $input): void
    {
        self::assertSame('12345678242', Normalizer::compact($input));
    }

    public function test_keeps_letters(): void
    {
        self::assertSame('hu17780010', Normalizer::compact('hu 1778-0010'));
    }
}
