<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TagServiceInterface
{
    /**
     * Default number of items per page for pagination.
     */
    public const DEFAULT_PER_PAGE = 15;

    /**
     * Get all tags with their related data.
     *
     * @param int $perPage Number of items per page
     * @param array<string, mixed> $filters Optional filters to apply
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage = self::DEFAULT_PER_PAGE, array $filters = []): LengthAwarePaginator;

    /**
     * Get all tags with their translation keys and translations.
     * Use this method when you need full translation data.
     *
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    public function getAllWithTranslations(int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator;

    /**
     * Create a new tag.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Tag;

    /**
     * Find a tag by ID or throw an exception.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id): Tag;

    /**
     * Find a tag by ID with translations or throw an exception.
     * Use this method when you need full translation data.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFailWithTranslations(string $id): Tag;

    /**
     * Update a tag.
     *
     * @param array<string, mixed> $data
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(string $id, array $data): Tag;

    /**
     * Delete a tag.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(string $id): void;
}
