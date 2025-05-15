<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Tag Model
 *
 * @property string $id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TranslationKey> $translationKeys
 *
 * @method static Builder searchByName(?string $name)
 */
class Tag extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    // Many-to-many relationship with Translation
    public function translations(): BelongsToMany
    {
        return $this->belongsToMany(Translation::class, 'tag_translation_key', 'tag_id', 'translation_key_id')
            ->withTimestamps();
    }

    /**
     * Get the translation keys that belong to the tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<TranslationKey>
     */
    public function translationKeys(): BelongsToMany
    {
        return $this->belongsToMany(TranslationKey::class, 'tag_translation_key')
            ->withTimestamps();
    }

    /**
     * Scope a query to search tags by name.
     */
    public function scopeSearchByName(Builder $query, ?string $name): Builder
    {
        return $query->when($name, function (Builder $query) use ($name): Builder {
            return $query->where('name', 'like', '%' . $name . '%');
        });
    }

    public function scopeWithTranslations(Builder $query): Builder
    {
        return $query->with('translations');
    }
}
