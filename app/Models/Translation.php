<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Translation Model
 *
 * @method static Builder byLocale(string $locale)
 * @method static Builder byTag(string $tag)
 */
class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'locale',
        'value'
    ];

    // Many-to-many relationship with Tag
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
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

    public function scopeSearchByKey(Builder $query, ?string $key): Builder
    {
        return $query->when($key, function ($query) use ($key) {
            $query->where('key', 'like', '%' . $key . '%');
        });
    }

    public function scopeSearchByValue(Builder $query, ?string $value): Builder
    {
        return $query->when($value, function ($query) use ($value) {
            $query->where('value', 'like', '%' . $value . '%');
        });
    }

    public function scopeFilterByLocale(Builder $query, ?string $locale): Builder
    {
        return $query->when($locale, function ($query) use ($locale) {
            $query->where('locale', $locale);
        });
    }

    public function scopeFilterByTags(Builder $query, ?array $tagIds): Builder
    {
        return $query->when($tagIds, function ($query) use ($tagIds) {
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        });
    }
}
