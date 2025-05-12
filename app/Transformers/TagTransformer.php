<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\Tag;
use App\Models\TranslationKey;
use App\Contracts\Transformers\TransformerInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class TagTransformer implements TransformerInterface
{
    /**
     * Transform a single tag into an array.
     *
     * @param Model $model
     * @return array<string, mixed>
     * @throws \InvalidArgumentException
     */
    public function transform(Model $model): array
    {
        if (!$model instanceof Tag) {
            throw new \InvalidArgumentException('Expected instance of Tag, got ' . get_class($model));
        }

        try {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'translation_keys' => $model->translationKeys->map(function (TranslationKey $key) {
                    return [
                        'id' => $key->id,
                        'key' => $key->key
                    ];
                })->toArray(),
                'created_at' => $model->created_at,
                'updated_at' => $model->updated_at
            ];
        } catch (\Exception $e) {
            Log::error('Error transforming tag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tag' => $model->toArray()
            ]);
            throw $e;
        }
    }

    /**
     * Transform a tag with its translations into an array.
     * Use this method when you need full translation data.
     *
     * @param Tag $tag
     * @return array<string, mixed>
     */
    public function transformWithTranslations(Tag $tag): array
    {
        try {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'translation_keys' => $tag->translationKeys->map(function (TranslationKey $key) {
                    return [
                        'id' => $key->id,
                        'key' => $key->key,
                        'translations' => $key->translations->mapWithKeys(function ($translation) {
                            return [$translation->locale => $translation->content];
                        })->toArray()
                    ];
                })->toArray(),
                'created_at' => $tag->created_at,
                'updated_at' => $tag->updated_at
            ];
        } catch (\Exception $e) {
            Log::error('Error transforming tag with translations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tag' => $tag->toArray()
            ]);
            throw $e;
        }
    }

    /**
     * Transform a collection of tags into an array.
     *
     * @param Collection<int, Tag> $tags
     * @return array<int, array<string, mixed>>
     */
    public function transformCollection(Collection $tags): array
    {
        try {
            return $tags->map(fn (Tag $tag) => $this->transform($tag))->toArray();
        } catch (\Exception $e) {
            Log::error('Error transforming tag collection', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Transform a collection of tags with translations into an array.
     * Use this method when you need full translation data.
     *
     * @param Collection<int, Tag> $tags
     * @return array<int, array<string, mixed>>
     */
    public function transformCollectionWithTranslations(Collection $tags): array
    {
        try {
            return $tags->map(fn (Tag $tag) => $this->transformWithTranslations($tag))->toArray();
        } catch (\Exception $e) {
            Log::error('Error transforming tag collection with translations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Transform a paginated collection of tags into an array.
     *
     * @param LengthAwarePaginator $paginator
     * @return array<string, mixed>
     */
    public function transformPaginated(LengthAwarePaginator $paginator): array
    {
        try {
            return [
                'data' => $this->transformCollection($paginator->getCollection()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error transforming paginated tags', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Transform a paginated collection of tags with translations into an array.
     * Use this method when you need full translation data.
     *
     * @param LengthAwarePaginator $paginator
     * @return array<string, mixed>
     */
    public function transformPaginatedWithTranslations(LengthAwarePaginator $paginator): array
    {
        try {
            return [
                'data' => $this->transformCollectionWithTranslations($paginator->getCollection()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error transforming paginated tags with translations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 