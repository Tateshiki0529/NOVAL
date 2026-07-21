<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Protocol extends Model
{
    use HasUuids;

    protected $fillable = ['owner_id', 'slug', 'visibility'];

    public function versions(): HasMany
    {
        return $this->hasMany(ProtocolVersion::class);
    }
}
