<?php

namespace App\Domain\Normalization;

use App\Domain\Validation\ValidationIssue;

final readonly class NormalizationResult
{
    /** @param list<ValidationIssue> $errors @param list<array{path:string,from:mixed,to:mixed}> $transformations */
    public function __construct(
        public array $value,
        public array $errors = [],
        public array $transformations = [],
    ) {}

    public function success(): bool
    {
        return $this->errors === [];
    }
}
