<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $logbook_id
 * @property string|null $current_revision_id
 * @property-read RecordRevision|null $currentRevision
 */
class ProtocolRecord extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'records';

    protected $fillable = ['logbook_id', 'current_revision_id'];

    public function logbook(): BelongsTo
    {
        return $this->belongsTo(Logbook::class);
    }

    /** @return BelongsTo<RecordRevision, $this> */
    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(RecordRevision::class, 'current_revision_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(RecordRevision::class, 'record_id');
    }
}
