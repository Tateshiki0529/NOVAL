<?php

namespace App\Application\Contracts;

use DateTimeImmutable;

interface RecordRepository
{
    public function createProtocolRecord(
        int $actorId,
        array $definition,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $receivedAt,
        array $payload,
        array $source,
        array $warnings,
    ): array;

    public function createOpenRecord(
        int $ownerId,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $receivedAt,
        array $input,
    ): array;

    public function reviseProtocolRecord(
        int $actorId,
        array $definition,
        string $baseRevisionId,
        string $operation,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $receivedAt,
        ?array $payload,
        array $source,
        array $warnings,
    ): array;

    public function reviseOpenRecord(
        int $ownerId,
        string $recordId,
        string $baseRevisionId,
        string $operation,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $receivedAt,
        array $input,
    ): array;
}
