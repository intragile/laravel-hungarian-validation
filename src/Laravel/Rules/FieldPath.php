<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel\Rules;

/**
 * @internal
 */
final class FieldPath
{
    /**
     * Resolves wildcards in a referenced field path against the attribute under
     * validation, position by position: `items.*.born` for `items.1.tax_id`
     * becomes `items.1.born`. A wildcard without a matching segment is kept.
     */
    public static function resolve(string $field, string $attribute): string
    {
        if (! str_contains($field, '*') || $attribute === '') {
            return $field;
        }

        $attributeSegments = explode('.', $attribute);
        $segments = explode('.', $field);

        foreach ($segments as $i => $segment) {
            if ($segment === '*' && isset($attributeSegments[$i])) {
                $segments[$i] = $attributeSegments[$i];
            }
        }

        return implode('.', $segments);
    }
}
