<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasUuids;

    protected $fillable = ['owner_id', 'name', 'description', 'visibility', 'sort_order'];

    public function logbooks(): HasMany
    {
        return $this->hasMany(Logbook::class);
    }
}
