<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TranslationTransformer
{
    public function transform(Translation $translation): array
    {
        try {
            return [
                'id' => $translation->id,
                'key' => $translation->translationKey?->key,
                'locale' => $translation->locale,
                'value' => $translation->content,
                'tags' => $translation->translationKey?->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name
                ])->toArray() ?? [],
                'created_at' => $translation->created_at,
                'updated_at' => $translation->updated_at
            ];
        } catch (\Exception $e) {
            Log::error('Error transforming translation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'translation' => $translation->toArray()
            ]);
            throw $e;
        }
    }

    public function transformWithRelations(Translation $translation): array
    {
        try {
            return [
                'id' => $translation->id,
                'key' => $translation->translationKey?->key,
                'locale' => $translation->locale,
                'value' => $translation->content,
                'translation_key' => [
                    'id' => $translation->translationKey?->id,
                    'key' => $translation->translationKey?->key,
                    'tags' => $translation->translationKey?->tags->map(fn ($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name
                    ])->toArray() ?? []
                ],
                'created_at' => $translation->created_at,
                'updated_at' => $translation->updated_at
            ];
        } catch (\Exception $e) {
            Log::error('Error transforming translation with relations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'translation' => $translation->toArray()
            ]);
            throw $e;
        }
    }

    public function transformCollection(\Illuminate\Database\Eloquent\Collection $translations): array
    {
        try {
            if ($translations->isEmpty()) {
                return [];
            }

            return $translations->map(fn (Translation $translation) => $this->transform($translation))->toArray();
        } catch (\Exception $e) {
            Log::error('Error transforming translation collection', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function transformCollectionWithRelations(\Illuminate\Database\Eloquent\Collection $translations): array
    {
        try {
            if ($translations->isEmpty()) {
                return [];
            }

            return $translations->map(fn (Translation $translation) => $this->transformWithRelations($translation))->toArray();
        } catch (\Exception $e) {
            Log::error('Error transforming translation collection with relations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function transformPaginated(\Illuminate\Pagination\LengthAwarePaginator $paginator): array
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
            Log::error('Error transforming paginated translations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function transformPaginatedWithRelations(\Illuminate\Pagination\LengthAwarePaginator $paginator): array
    {
        try {
            return [
                'data' => $this->transformCollectionWithRelations($paginator->getCollection()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error transforming paginated translations with relations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
