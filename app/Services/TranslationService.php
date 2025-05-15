<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Contracts\Services\TranslationServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\TranslationKey;
use App\Exceptions\DuplicateTranslationException;

class TranslationService implements TranslationServiceInterface
{
    public function getAll(int $perPage = self::DEFAULT_PER_PAGE, array $filters = []): LengthAwarePaginator
    {
        try {
            Log::info('Starting to fetch translations', [
                'perPage' => $perPage,
                'filters' => $filters
            ]);
            $query = Translation::query();
            $query = $query->with(['translationKey' => function ($query) {
                $query->select('id', 'key')
                    ->with(['tags' => function ($query) {
                        $query->select('tags.id', 'tags.name');
                    }]);
            }]);

            // Apply filters using when pattern
            $query->when(!empty($filters), function ($query) use ($filters) {
                $query->when(isset($filters['key']), fn($q) => $q->searchByKey($filters['key']))
                      ->when(isset($filters['value']), fn($q) => $q->searchByValue($filters['value']))
                      ->when(isset($filters['locale']), fn($q) => $q->filterByLocale($filters['locale']))
                      ->when(isset($filters['tag']), fn($q) => $q->byTag($filters['tag']));
            });

            $translations = $query->paginate($perPage);

            Log::info('Successfully fetched translations', [
                'total' => $translations->total(),
                'perPage' => $translations->perPage(),
                'currentPage' => $translations->currentPage(),
                'hasFilters' => !empty($filters)
            ]);

            return $translations;
        } catch (\Exception $e) {
            Log::error('Error in TranslationService::getAll', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function getAllWithRelations(int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        try {
            Log::info('Starting to fetch translations with full relations', ['perPage' => $perPage]);
            
            $translations = Translation::with(['translationKey.tags', 'tags'])
                ->paginate($perPage);

            Log::info('Successfully fetched translations with full relations', [
                'total' => $translations->total(),
                'perPage' => $translations->perPage(),
                'currentPage' => $translations->currentPage()
            ]);

            return $translations;
        } catch (\Exception $e) {
            Log::error('Error in TranslationService::getAllWithRelations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            throw $e;
        }
    }

    public function create(array $data): Translation
    {
        try {
            return DB::transaction(function () use ($data) {
                // First create or find the translation key
                $translationKey = TranslationKey::firstOrCreate(
                    ['key' => $data['key']],
                    ['id' => (string) Str::uuid()]
                );

                // Check if translation already exists
                $existingTranslation = Translation::where('translation_key_id', $translationKey->id)
                    ->where('locale', $data['locale'])
                    ->first();

                if ($existingTranslation) {
                    throw new DuplicateTranslationException(
                        "A translation with key '{$data['key']}' and locale '{$data['locale']}' already exists."
                    );
                }

                // Create the translation with the translation key ID
                $translation = Translation::create([
                    'id' => (string) Str::uuid(),
                    'translation_key_id' => $translationKey->id,
                    'locale' => $data['locale'],
                    'content' => $data['value']
                ]);

                // Sync tags if provided
                if (isset($data['tag_ids'])) {
                    $syncData = collect($data['tag_ids'])->mapWithKeys(function ($tagId) {
                        return [$tagId => ['id' => (string) Str::uuid()]];
                    })->all();
                    $translationKey->tags()->sync($syncData);
                }

                return $translation;
            });
        } catch (DuplicateTranslationException $e) {
            Log::warning('Attempted to create duplicate translation', [
                'key' => $data['key'],
                'locale' => $data['locale'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Find a translation by ID or throw an exception.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id): Translation
    {
        return Translation::with('tags')->findOrFail($id);
    }

    /**
     * Update a translation.
     *
     * @param array<string, mixed> $data
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(string $id, array $data): Translation
    {
        return DB::transaction(function () use ($id, $data) {
            $translation = Translation::with('translationKey')->findOrFail($id);
            $newLocale = $data['locale'] ?? $translation->locale;
            
            // If key is being updated, update or create new translation key
            if (isset($data['key']) && $data['key'] !== $translation->translationKey->key) {
                // Check if a translation with the new key and same locale already exists
                $existingTranslation = Translation::whereHas('translationKey', function ($query) use ($data) {
                    $query->where('key', $data['key']);
                })->where('locale', $newLocale)->first();

                if ($existingTranslation) {
                    throw new \Exception("A translation with key '{$data['key']}' and locale '{$newLocale}' already exists.");
                }

                // Create new translation key
                $translationKey = TranslationKey::create([
                    'key' => $data['key'],
                    'id' => (string) Str::uuid()
                ]);

                // Update translation to use the new key and update other fields
                $translation->translation_key_id = $translationKey->id;
                if (isset($data['locale'])) {
                    $translation->locale = $data['locale'];
                }
                if (isset($data['value'])) {
                    $translation->content = $data['value'];
                }
                $translation->save();

                // If there are tags, sync them with the new translation key
                if (isset($data['tag_ids'])) {
                    $syncData = collect($data['tag_ids'])->mapWithKeys(function ($tagId) {
                        return [$tagId => ['id' => (string) Str::uuid()]];
                    })->all();
                    $translationKey->tags()->sync($syncData);
                } else {
                    // If no new tags provided, copy tags from the old translation key with new UUIDs
                    $oldTranslationKey = $translation->getOriginal('translation_key_id');

                    $oldTags = TranslationKey::with('tags')->find($oldTranslationKey)?->tags ?? collect();

                    $syncData = $oldTags->mapWithKeys(function ($tag) {
                        return [$tag->id => ['id' => (string) Str::uuid()]];
                    })->all();

                    $translationKey->tags()->sync($syncData);
                }
            } else {
                // Update translation fields
                if (isset($data['locale'])) {
                    // Check if a translation with the same key and new locale already exists
                    $existingTranslation = Translation::whereHas('translationKey', function ($query) use ($translation) {
                        $query->where('key', $translation->translationKey->key);
                    })->where('locale', $data['locale'])
                      ->where('id', '!=', $translation->id)
                      ->first();

                    if ($existingTranslation) {
                        throw new \Exception("A translation with key '{$translation->translationKey->key}' and locale '{$data['locale']}' already exists.");
                    }

                    $translation->locale = $data['locale'];
                }
                if (isset($data['value'])) {
                    $translation->content = $data['value'];
                }
                $translation->save();

                // Sync tags with the existing translation key if provided
                if (isset($data['tag_ids'])) {
                    $syncData = collect($data['tag_ids'])->mapWithKeys(function ($tagId) {
                        return [$tagId => ['id' => (string) Str::uuid()]];
                    })->all();
                    $translation->translationKey->tags()->sync($syncData);
                }
            }

            // Always refresh the translation with its relationships
            $translation = $translation->fresh(['translationKey.tags' => function ($query) {
                $query->select('tags.id', 'tags.name');
            }]);
            
            return $translation;
        });
    }

    /**
     * Delete a translation and its associated translation key if it's the last translation.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(string $id): void
    {
        DB::transaction(function () use ($id) {
            // Get the translation with its key
            $translation = Translation::with('translationKey')->findOrFail($id);
            $translationKeyId = $translation->translation_key_id;
            
            // Delete the translation
            $translation->delete();
            
            // Check if this was the last translation for this key
            $hasRemainingTranslations = Translation::where('translation_key_id', $translationKeyId)->exists();
            
            if (!$hasRemainingTranslations) {
                // Get the translation key
                $translationKey = TranslationKey::find($translationKeyId);
                if ($translationKey) {
                    // Delete all tag associations
                    DB::table('tag_translation_key')->where('translation_key_id', $translationKeyId)->delete();
                    // Delete the translation key
                    $translationKey->delete();
                }
            }
        });
    }

    /**
     * Export all translations in a format suitable for frontend applications.
     * Uses raw queries and caching for optimal performance.
     * 
     * @return array
     */
    public function export(): array
    {
        try {
            Log::info('Starting export in TranslationService');
            
            // Try to get from cache first
            return cache()->remember(
                self::EXPORT_CACHE_KEY,
                self::EXPORT_CACHE_DURATION,
                function () {
                    Log::info('Cache miss, preparing export data');
                    
                    $exportData = [];
                    $chunkSize = 1000; // Process 1000 records at a time
                    $processedKeys = 0;
                    
                    // Get all translation keys first
                    $translationKeys = DB::table('translation_keys')
                        ->select('id', 'key')
                        ->orderBy('key')
                        ->take(2000)
                        ->get();
                    
                    Log::info('Found translation keys', ['total_keys' => $translationKeys->count()]);
                    
                    // Process in chunks
                    foreach ($translationKeys->chunk($chunkSize) as $keyChunk) {
                        // Properly format UUIDs for SQL query
                        $keyIds = $keyChunk->pluck('id')->map(function ($id) {
                            return "'" . $id . "'";
                        })->toArray();
                        
                        // Get translations and tags for this chunk
                        $result = DB::select("
                            SELECT 
                                tk.key,
                                t.locale,
                                t.content,
                                GROUP_CONCAT(DISTINCT tg.name) as tags
                            FROM translation_keys tk
                            LEFT JOIN translations t ON t.translation_key_id = tk.id
                            LEFT JOIN tag_translation_key tkt ON tkt.translation_key_id = tk.id
                            LEFT JOIN tags tg ON tg.id = tkt.tag_id
                            WHERE tk.id IN (" . implode(',', $keyIds) . ")
                            GROUP BY tk.key, t.locale, t.content
                            ORDER BY tk.key, t.locale
                        ");
                        
                        // Process the results for this chunk
                        foreach ($result as $row) {
                            if (!isset($exportData[$row->key])) {
                                $exportData[$row->key] = [
                                    'key' => $row->key,
                                    'translations' => [],
                                    'tags' => []
                                ];
                            }
                            
                            // Only add translation if both locale and content are not null
                            if ($row->locale !== null && $row->content !== null) {
                                $exportData[$row->key]['translations'][$row->locale] = $row->content;
                            }
                            
                            // Add tags if they exist
                            if ($row->tags !== null) {
                                $exportData[$row->key]['tags'] = array_unique(
                                    array_merge(
                                        $exportData[$row->key]['tags'],
                                        explode(',', $row->tags)
                                    )
                                );
                            }
                        }
                        
                        $processedKeys += count($keyIds);
                        Log::info('Processed chunk', [
                            'processed_keys' => $processedKeys,
                            'total_keys' => $translationKeys->count(),
                            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB'
                        ]);
                        
                        // Clear memory after each chunk
                        unset($result);
                        gc_collect_cycles();
                    }
                    
                    // Convert to array and sort by key
                    $exportData = array_values($exportData);
                    usort($exportData, function($a, $b) {
                        return strcmp($a['key'], $b['key']);
                    });
                    
                    Log::info('Export completed', [
                        'total_keys' => count($exportData),
                        'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB'
                    ]);
                    
                    return $exportData;
                }
            );
        } catch (\Exception $e) {
            Log::error('Error in TranslationService::export', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB'
            ]);
            throw $e;
        }
    }

    /**
     * Clear the translations export cache.
     * Call this method whenever translations are updated.
     */
    public function clearExportCache(): void
    {
        cache()->forget(self::EXPORT_CACHE_KEY);
    }
}
