<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Translation Model
 *
 * @property string $id
 * @property string $translation_key_id
 * @property string $locale
 * @property string $content
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read TranslationKey $translationKey
 *
 * @method static Builder byLocale(string $locale)
 * @method static Builder byTag(string $tag)
 * @method static Builder searchByKey(?string $key)
 * @method static Builder searchByValue(?string $value)
 * @method static Builder filterByLocale(?string $locale)
 * @method static Builder filterByTags(?array $tagIds)
 */
class Translation extends Model
{
    use HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'translation_key_id',
        'locale',
        'content',
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

    /**
     * Get the translation key that owns the translation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<TranslationKey>
     */
    public function translationKey(): BelongsTo
    {
        return $this->belongsTo(TranslationKey::class);
    }

    /**
     * Scope a query to filter translations by locale.
     *
     * @throws \InvalidArgumentException
     */
    public function scopeByLocale(Builder $query, string $locale): Builder
    {
        if (strlen($locale) > 5) {
            throw new \InvalidArgumentException('Locale must be at most 5 characters long');
        }

        return $query->where('locale', $locale);
    }

    /**
     * Scope a query to search translations by key.
     */
    public function scopeSearchByKey(Builder $query, ?string $key): Builder
    {
        return $query->when($key, function (Builder $query) use ($key): Builder {
            return $query->whereHas('translationKey', function (Builder $query) use ($key): void {
                $query->where('key', 'like', '%' . $key . '%');
            });
        });
    }

    /**
     * Scope a query to search translations by content.
     */
    public function scopeSearchByValue(Builder $query, ?string $value): Builder
    {
        return $query->when($value, function (Builder $query) use ($value): Builder {
            return $query->where('content', 'like', '%' . $value . '%');
        });
    }

    /**
     * Scope a query to filter translations by locale.
     *
     * @throws \InvalidArgumentException
     */
    public function scopeFilterByLocale(Builder $query, ?string $locale): Builder
    {
        if ($locale !== null && strlen($locale) > 5) {
            throw new \InvalidArgumentException('Locale must be at most 5 characters long');
        }

        return $query->when($locale, function (Builder $query) use ($locale): Builder {
            return $query->where('locale', $locale);
        });
    }

    /**
     * Scope a query to filter translations by tags.
     *
     * @param array<int, string>|null $tagIds
     */
    public function scopeFilterByTags(Builder $query, ?array $tagIds): Builder
    {
        return $query->when($tagIds, function (Builder $query) use ($tagIds): Builder {
            return $query->whereHas('translationKey.tags', function (Builder $query) use ($tagIds): void {
                $query->whereIn('tags.id', $tagIds);
            });
        });
    }
}
