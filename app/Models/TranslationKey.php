<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id
 * @property string $key
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Translation> $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tag> $tags
 */
class TranslationKey extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
    ];

    /**
     * Get the translations for the translation key.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Translation>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    /**
     * Get the tags for the translation key.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Tag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tag_translation_key')
            ->withTimestamps();
    }

    /**
     * Get a translation for a specific locale.
     *
     * @throws \InvalidArgumentException
     */
    public function getTranslation(string $locale): ?Translation
    {
        if (strlen($locale) > 5) {
            throw new \InvalidArgumentException('Locale must be at most 5 characters long');
        }

        return $this->translations()->where('locale', $locale)->first();
    }

    /**
     * Get a translation for a specific locale with fallback.
     *
     * @throws \InvalidArgumentException
     */
    public function getTranslationWithFallback(string $locale, string $fallbackLocale = 'en'): ?Translation
    {
        if (strlen($locale) > 5 || strlen($fallbackLocale) > 5) {
            throw new \InvalidArgumentException('Locale must be at most 5 characters long');
        }

        return $this->getTranslation($locale) ?? $this->getTranslation($fallbackLocale);
    }
} 