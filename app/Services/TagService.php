<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

class TagService
{
    public function getAll(): Collection
    {
        return Tag::with(['translations' => function($query) {
            $query->select('translations.id', 'translations.key', 'translations.locale', 'translations.value');
        }])->get();
    }

    public function create(array $data): Tag
    {
        return Tag::create($data);
    }

    public function findOrFail(int $id): Tag
    {
        return Tag::with('translations')->findOrFail($id);
    }

    public function update(int $id, array $data): Tag
    {
         $tag = Tag::with('translations')->findOrFail($id);
         $tag->update($data);
         return $tag;
    }

    public function delete(int $id): void
    {
         Tag::findOrFail($id)->delete();
    }
} 