<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\TranslationKey;
use Illuminate\Database\Eloquent\Collection;

interface TranslationKeyServiceInterface
{
    /**
     * Get all translation keys with their translations.
     *
     * @return Collection<int, TranslationKey>
     */
    public function getAll(): Collection;

    /**
     * Create a new translation key.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): TranslationKey;

    /**
     * Find a translation key by ID or throw an exception.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id): TranslationKey;

    /**
     * Update a translation key.
     *
     * @param array<string, mixed> $data
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(string $id, array $data): TranslationKey;

    /**
     * Delete a translation key.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(string $id): void;

    /**
     * Search translation keys by various criteria.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, TranslationKey>
     */
    public function search(array $filters): Collection;
} 