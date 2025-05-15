<?php

declare(strict_types=1);

namespace App\Contracts\Transformers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransformerInterface
{
    /**
     * Transform a single model instance.
     *
     * @param Model $model
     * @return array<string, mixed>
     */
    public function transform(Model $model): array;

    /**
     * Transform a collection of models.
     *
     * @param Collection<int, Model> $collection
     * @return array<int, array<string, mixed>>
     */
    public function transformCollection(Collection $collection): array;

    /**
     * Transform a paginated collection of models.
     *
     * @return array{
     *     data: array<int, array<string, mixed>>,
     *     meta: array{
     *         current_page: int,
     *         last_page: int,
     *         per_page: int,
     *         total: int
     *     }
     * }
     */
    public function transformPaginated(LengthAwarePaginator $paginator): array;
} 