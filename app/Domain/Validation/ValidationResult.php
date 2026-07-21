<?php

namespace App\Domain\Validation;

final readonly class ValidationResult
{
    /** @param list<ValidationIssue> $errors @param list<ValidationIssue> $warnings */
    public function __construct(
        public array $errors = [],
        public array $warnings = [],
    ) {}

    public function valid(): bool
    {
        return $this->errors === [];
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->valid(),
            'errors' => array_map(static fn (ValidationIssue $issue): array => $issue->toArray(), $this->errors),
            'warnings' => array_map(static fn (ValidationIssue $issue): array => $issue->toArray(), $this->warnings),
        ];
    }
}
