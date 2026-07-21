<?php

namespace App\Application\UseCases;

use App\Application\Contracts\RecordRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class CreateOpenRecord
{
    public function __construct(private RecordRepository $records) {}

    public function execute(int $ownerId, array $input): array
    {
        $body = (string) ($input['body'] ?? '');
        $title = trim((string) ($input['title'] ?? ''));
        $tags = array_values(array_unique(array_map('trim', (array) ($input['tags'] ?? []))));

        if ($body === '' || mb_strlen($body) > 65_536) {
            throw new InvalidArgumentException('Open Record body is invalid.');
        }
        if (mb_strlen($title) > 200 || count($tags) > 20) {
            throw new InvalidArgumentException('Open Record metadata is invalid.');
        }
        foreach ($tags as $tag) {
            if ($tag === '' || mb_strlen($tag) > 64) {
                throw new InvalidArgumentException('Open Record tag is invalid.');
            }
        }

        return $this->records->createOpenRecord(
            $ownerId,
            new DateTimeImmutable((string) ($input['occurredAt'] ?? 'now')),
            new DateTimeImmutable('now'),
            [
                'category_id' => ($input['categoryId'] ?? null) ?: null,
                'title' => $title ?: null,
                'body' => $body,
                'tags' => $tags ?: null,
                'visibility' => 'private',
                'source' => ['type' => 'web'],
                'validation_warnings' => [],
            ],
        );
    }
}
