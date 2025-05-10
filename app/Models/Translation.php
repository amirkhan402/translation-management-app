<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Translation Model
 *
 * @method static Builder byLocale(string $locale)
 * @method static Builder byTag(string $tag)
 */
class Translation extends Model
{
    protected $fillable = ['key', 'locale', 'value'];

    // Many-to-many relationship with Tag
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tag_translation');
    }

    // Scope for searching by tag name
    public function scopeByTag(Builder $query, string $tag): Builder
    {
        return $query->whereHas('tags', function ($q) use ($tag) {
            $q->where('name', $tag);
        });
    }

    // Scope to filter translations by locale
    public function scopeByLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }
}
