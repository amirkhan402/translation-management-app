<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

interface TagServiceInterface
{
    /**
     * Get all tags with their related data.
     *
     * @return Collection<int, Tag>
     */
    public function getAll(): Collection;

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

    /**
     * Search tags by name.
     *
     * @param string|null $name
     * @return Collection<int, Tag>
     */
    public function searchByName(?string $name): Collection;
} 