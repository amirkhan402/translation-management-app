<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\TagServiceInterface;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TagService implements TagServiceInterface
{
    /**
     * Default number of items per page for pagination.
     */
    private const DEFAULT_PER_PAGE = 15;

    /**
     * Get all tags with their related data.
     *
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        try {
            // Get paginated tags with a limited number of translation keys
            $tags = Tag::with(['translationKeys' => function ($query) {
                $query->select('translation_keys.id', 'translation_keys.key')
                    ->limit(100); // Limit the number of translation keys per tag
            }])->paginate($perPage);

            return $tags;
        } catch (\Exception $e) {
            Log::error('Error in TagService::getAll', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            throw $e;
        }
    }

    /**
     * Get all tags with their translation keys and translations.
     * Use this method when you need full translation data.
     *
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    public function getAllWithTranslations(int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return Tag::with(['translationKeys.translations'])
            ->paginate($perPage);
    }

    /**
     * Create a new tag.
     *
     * @param array<string, mixed> $data
     * @throws \Exception
     */
    public function create(array $data): Tag
    {
        try {
            return Tag::create($data);
        } catch (\Exception $e) {
            Log::error('Error creating tag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Find a tag by ID or throw an exception.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(string $id): Tag
    {
        try {
            return Tag::with(['translationKeys'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::error('Tag not found', ['id' => $id]);
            throw $e;
        }
    }

    /**
     * Find a tag by ID with translations or throw an exception.
     * Use this method when you need full translation data.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFailWithTranslations(string $id): Tag
    {
        try {
            return Tag::with(['translationKeys.translations'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::error('Tag not found', ['id' => $id]);
            throw $e;
        }
    }

    /**
     * Update a tag.
     *
     * @param array<string, mixed> $data
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function update(string $id, array $data): Tag
    {
        try {
            $tag = $this->findOrFail($id);
            $tag->update($data);
            return $tag->fresh(['translationKeys']);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error updating tag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Delete a tag.
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function delete(string $id): void
    {
        try {
            $tag = $this->findOrFail($id);
            $tag->delete();
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error deleting tag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * Search tags by name.
     *
     * @param string|null $name
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    public function searchByName(?string $name, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return Tag::searchByName($name)
            ->with(['translationKeys'])
            ->paginate($perPage);
    }
} 