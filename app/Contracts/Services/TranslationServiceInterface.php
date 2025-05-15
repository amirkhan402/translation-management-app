<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TranslationServiceInterface
{
    /**
     * Default number of items per page for pagination.
     */
    public const DEFAULT_PER_PAGE = 15;

    /**
     * Cache key for translations export.
     */
    public const EXPORT_CACHE_KEY = 'translations_export';

    /**
     * Cache duration for translations export in seconds.
     */
    public const EXPORT_CACHE_DURATION = 60;

    /**
     * Get all translations with their related data.
     *
     * @param int $perPage Number of items per page
     * @param array<string, mixed> $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = self::DEFAULT_PER_PAGE, array $filters = []): LengthAwarePaginator;

    /**
     * Get all translations with their translation keys and tags.
     * Use this method when you need full translation data.
     *
     * @param int $perPage Number of items per page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllWithRelations(int $perPage = self::DEFAULT_PER_PAGE): \Illuminate\Pagination\LengthAwarePaginator;

    /**
     * Create a new translation.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Translation;

    /**
     * Find a translation by ID or throw an exception.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id): Translation;

    /**
     * Update a translation.
     *
     * @param array<string, mixed> $data
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(string $id, array $data): Translation;

    /**
     * Delete a translation.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(string $id): void;

    /**
     * Export translations in a format suitable for frontend use.
     *
     * @return array<int, array<string, mixed>>
     */
    public function export(): array;

    /**
     * Clear the translations export cache.
     */
    public function clearExportCache(): void;
}
