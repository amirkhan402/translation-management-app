<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Tag Model
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Many-to-many relationship with Translation
    public function translations(): BelongsToMany
    {
        return $this->belongsToMany(Translation::class);
    }

    public function scopeSearchByName(Builder $query, ?string $name): Builder
    {
        return $query->when($name, function ($query) use ($name) {
            $query->where('name', 'like', '%' . $name . '%');
        });
    }

    public function scopeWithTranslations(Builder $query): Builder
    {
        return $query->with('translations');
    }
}
