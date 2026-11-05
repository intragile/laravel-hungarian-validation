<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

use Stringable;

/**
 * A validated Hungarian identifier.
 *
 * Instances are immutable and always hold a valid value. Input is normalised
 * before validation: whitespace, hyphens and dashes, dots and slashes are ignored.
 */
interface Identifier extends Stringable
{
    /**
     * @throws InvalidIdentifierException
     */
    public static function parse(string $value): static;

    public static function tryParse(string $value): ?static;

    public static function isValid(string $value): bool;

    /**
     * Returns the first rule the value breaks, or null when it is valid.
     */
    public static function validate(string $value): ?Violation;

    /**
     * The canonical compact form, without separators.
     */
    public function value(): string;

    /**
     * The canonical human-readable form.
     */
    public function format(): string;

    public function equals(Identifier $other): bool;
}
