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
 * @property string|null $current_revision_id
 * @property-read OpenRecordRevision|null $currentRevision
 */
class OpenRecord extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = ['owner_id', 'category_id', 'current_revision_id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<OpenRecordRevision, $this> */
    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(OpenRecordRevision::class, 'current_revision_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(OpenRecordRevision::class, 'record_id');
    }
}
