<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $record_id
 * @property int $revision_number
 * @property string $operation
 * @property string|null $title
 * @property string $body
 * @property array<int, string>|null $tags
 * @property string $visibility
 * @property CarbonImmutable $occurred_at
 * @property CarbonImmutable $received_at
 * @property array<string, mixed> $source
 * @property array<int, mixed> $validation_warnings
 */
class OpenRecordRevision extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'record_id', 'category_id_snapshot', 'revision_number',
        'parent_revision_id', 'operation', 'title', 'body', 'tags',
        'visibility', 'occurred_at', 'received_at', 'source',
        'validation_warnings', 'actor_id',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'occurred_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
            'source' => 'array',
            'validation_warnings' => 'array',
        ];
    }
}
