<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation;

use InvalidArgumentException;

final class InvalidIdentifierException extends InvalidArgumentException
{
    /**
     * @param  string  $identifierType  Human-readable English name, e.g. "tax number".
     */
    public function __construct(
        public readonly Violation $violation,
        public readonly string $identifierType,
    ) {
        parent::__construct(sprintf('Invalid Hungarian %s: %s.', $identifierType, $violation->description()));
    }
}
