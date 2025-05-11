<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TagTransformer
{
    public function transform(Tag $tag): array
    {
        try {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'translations' => $tag->translations->groupBy('key')
                    ->map(function ($items) {
                        try {
                            return [
                                'key' => $items->first()->key,
                                'translations' => $items->mapWithKeys(function ($item) {
                                    return [$item->locale => $item->value];
                                })
                            ];
                        } catch (\Exception $e) {
                            Log::error('Error transforming translation items', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                                'items' => $items->toArray()
                            ]);
                            throw $e;
                        }
                    })->values(),
                'created_at' => $tag->created_at,
                'updated_at' => $tag->updated_at
            ];
        } catch (\Exception $e) {
            Log::error('Error transforming tag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tag' => $tag->toArray()
            ]);
            throw $e;
        }
    }

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
            Log::error('Error transforming paginated tags', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 