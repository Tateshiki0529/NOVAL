<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $record_id
 * @property string|null $parent_revision_id
 * @property string $protocol_version_id
 * @property int $revision_number
 * @property string $operation
 * @property CarbonImmutable $occurred_at
 * @property CarbonImmutable $received_at
 * @property array<string, mixed> $payload
 * @property array<string, mixed> $source
 * @property array<int, mixed> $validation_warnings
 */
class RecordRevision extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'record_id', 'revision_number', 'parent_revision_id',
        'protocol_version_id', 'operation', 'occurred_at', 'received_at',
        'payload', 'source', 'validation_warnings', 'actor_id',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
            'payload' => 'array',
            'source' => 'array',
            'validation_warnings' => 'array',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(ProtocolRecord::class, 'record_id');
    }
}
