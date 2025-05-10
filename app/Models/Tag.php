<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Tag Model
 */
class Tag extends Model
{
    protected $fillable = ['name'];

    // Many-to-many relationship with Translation
    public function translations(): BelongsToMany
    {
        return $this->belongsToMany(Translation::class, 'tag_translation');
    }
}
