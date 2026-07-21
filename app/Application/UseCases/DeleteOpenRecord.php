<?php

namespace App\Application\UseCases;

use App\Application\Contracts\RecordRepository;
use DateTimeImmutable;

final readonly class DeleteOpenRecord
{
    public function __construct(private RecordRepository $records) {}

    public function execute(int $ownerId, string $recordId, string $baseRevisionId): array
    {
        return $this->records->reviseOpenRecord(
            $ownerId,
            $recordId,
            $baseRevisionId,
            'delete',
            new DateTimeImmutable('now'),
            new DateTimeImmutable('now'),
            [],
        );
    }
}
