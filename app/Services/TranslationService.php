<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TranslationService
{
    public function getAll(): Collection
    {
        return Translation::with('tags')->get();
    }

    public function create(array $data): Translation
    {
        return DB::transaction(function () use ($data) {
            $translation = Translation::create($data);
            if (isset($data['tag_ids'])) {
                $translation->tags()->sync($data['tag_ids']);
            }
            return $translation;
        });
    }

    public function findOrFail(int $id): Translation
    {
        return Translation::with('tags')->findOrFail($id);
    }

    public function update(int $id, array $data): Translation
    {
        return DB::transaction(function () use ($id, $data) {
            $translation = Translation::findOrFail($id);
            $translation->update($data);
            if (isset($data['tag_ids'])) {
                $translation->tags()->sync($data['tag_ids']);
            } else {
                $translation->tags()->detach();
            }
            return $translation;
        });
    }

    public function delete(int $id): void
    {
        Translation::findOrFail($id)->delete();
    }

    public function bulkCreate(array $translations): Collection
    {
        return DB::transaction(function () use ($translations) {
            $created = new Collection();
            foreach ($translations as $data) {
                $created->push($this->create($data));
            }
            return $created;
        });
    }

    public function export(): array
    {
        $translations = Translation::with('tags')->get();
        return $translations->groupBy('key')->map(function ($items) {
            $locales = $items->mapWithKeys(function ($item) {
                return [$item->locale => $item->value];
            });
            $tags = $items->first()->tags->pluck('name')->toArray();
            return [
                'key' => $items->first()->key,
                'translations' => $locales,
                'tags' => $tags,
            ];
        })->values()->toArray();
    }

    public function search(array $filters)
    {
        $query = Translation::with('tags')
            ->searchByKey($filters['key'] ?? null)
            ->searchByValue($filters['value'] ?? null)
            ->filterByLocale($filters['locale'] ?? null)
            ->when(isset($filters['tag']), function ($q) use ($filters) {
                $q->byTag($filters['tag']);
            });
        return $query->get();
    }
} 