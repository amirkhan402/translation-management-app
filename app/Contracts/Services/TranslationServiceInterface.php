<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Collection;

interface TranslationServiceInterface
{
    /**
     * Get all translations with their related data.
     *
     * @return Collection<int, Translation>
     */
    public function getAll(): Collection;

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
     * Bulk create translations.
     *
     * @param array<int, array<string, mixed>> $translations
     * @return Collection<int, Translation>
     */
    public function bulkCreate(array $translations): Collection;

    /**
     * Export translations in a format suitable for frontend use.
     *
     * @return array<int, array<string, mixed>>
     */
    public function export(): array;

    /**
     * Search translations by various criteria.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, Translation>
     */
    public function search(array $filters): Collection;
} 