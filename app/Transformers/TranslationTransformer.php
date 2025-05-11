<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Collection;

class TranslationTransformer
{
    public function transform(Translation $translation): array
    {
         return [
             'id' => $translation->id,
             'key' => $translation->key,
             'locale' => $translation->locale,
             'value' => $translation->value,
             'tags' => (new TagTransformer())->transformCollection($translation->tags),
             'created_at' => $translation->created_at,
             'updated_at' => $translation->updated_at
         ];
    }

    public function transformCollection(Collection $translations): array
    {
         if ($translations->isNotEmpty() && $translations->first()->hasAttribute('key')) {
             return $translations->groupBy('key')
                 ->map(function ($items) {
                     return [
                         'key' => $items->first()->key,
                         'translations' => $items->mapWithKeys(function ($item) {
                             return [$item->locale => $item->value];
                         })
                     ];
                 })->values()->toArray();
         } else {
             return $translations->map(fn (Translation $translation) => $this->transform($translation))->toArray();
         }
    }

    public function transformPaginated(\Illuminate\Pagination\LengthAwarePaginator $paginator): array
    {
         return [
             'data' => $this->transformCollection($paginator->getCollection()),
             'meta' => [
                 'current_page' => $paginator->currentPage(),
                 'last_page' => $paginator->lastPage(),
                 'per_page' => $paginator->perPage(),
                 'total' => $paginator->total()
             ]
         ];
    }
} 