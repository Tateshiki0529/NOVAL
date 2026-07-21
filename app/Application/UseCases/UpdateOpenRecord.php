<?php

namespace App\Application\UseCases;

use App\Application\Contracts\RecordRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class UpdateOpenRecord
{
    public function __construct(private RecordRepository $records) {}

    public function execute(int $ownerId, string $recordId, array $input): array
    {
        $body = (string) ($input['body'] ?? '');
        $title = trim((string) ($input['title'] ?? ''));
        $tags = array_values(array_unique(array_map('trim', (array) ($input['tags'] ?? []))));
        if ($body === '' || mb_strlen($body) > 65_536 || mb_strlen($title) > 200 || count($tags) > 20) {
            throw new InvalidArgumentException('Open Record input is invalid.');
        }

        return $this->records->reviseOpenRecord(
            $ownerId,
            $recordId,
            (string) ($input['baseRevisionId'] ?? ''),
            'update',
            new DateTimeImmutable((string) ($input['occurredAt'] ?? 'now')),
            new DateTimeImmutable('now'),
            [
                'category_id' => ($input['categoryId'] ?? null) ?: null,
                'title' => $title ?: null,
                'body' => $body,
                'tags' => $tags ?: null,
                'source' => ['type' => 'web'],
            ],
        );
    }
}
