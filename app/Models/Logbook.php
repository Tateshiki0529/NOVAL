<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property int $owner_id
 * @property string|null $category_id
 * @property string $current_protocol_version_id
 * @property-read ProtocolVersion $currentProtocolVersion
 */
class Logbook extends Model
{
    use HasUuids;

    protected $fillable = [
        'owner_id', 'category_id', 'name', 'description', 'visibility',
        'current_protocol_version_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<ProtocolVersion, $this> */
    public function currentProtocolVersion(): BelongsTo
    {
        return $this->belongsTo(ProtocolVersion::class, 'current_protocol_version_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(ProtocolRecord::class);
    }
}
