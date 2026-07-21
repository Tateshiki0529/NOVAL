<?php

namespace App\Domain\Validation;

final readonly class ValidationIssue
{
    public function __construct(
        public string $severity,
        public string $path,
        public string $rule,
        public string $code,
        public string $messageKey,
        public array $params = [],
        public ?string $schemaPath = null,
        public mixed $expected = null,
        public mixed $actual = null,
        public ?string $message = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'severity' => $this->severity,
            'path' => $this->path,
            'schemaPath' => $this->schemaPath,
            'rule' => $this->rule,
            'code' => $this->code,
            'messageKey' => $this->messageKey,
            'params' => $this->params ?: null,
            'expected' => $this->expected,
            'actual' => $this->actual,
            'message' => $this->message,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
