<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property string $id
 * @property string $protocol_id
 * @property string $version
 * @property string $state
 * @property array<string, mixed> $schema
 * @property array<string, mixed> $metadata
 * @property array<int, mixed> $advisories
 * @property string|null $schema_hash
 * @property string|null $content_hash
 * @property CarbonImmutable|null $published_at
 * @property-read Protocol $protocol
 */
class ProtocolVersion extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'protocol_id', 'version', 'state', 'schema', 'metadata', 'advisories',
        'schema_hash', 'content_hash', 'hash_algorithm',
        'canonicalization_version', 'revision', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'metadata' => 'array',
            'advisories' => 'array',
            'published_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            if ($version->getOriginal('state') === 'published') {
                throw new LogicException('Published Protocol Versions are immutable.');
            }
        });
    }

    /** @return BelongsTo<Protocol, $this> */
    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class);
    }
}
