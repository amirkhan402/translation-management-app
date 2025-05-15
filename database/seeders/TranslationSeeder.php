<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TranslationSeeder extends Seeder
{
    private const TOTAL_RECORDS = 100000;
    private const BATCH_SIZE = 1000; // Increased batch size for better performance
    private const LOCALES = ['en', 'es', 'fr', 'de', 'it', 'pt', 'nl', 'ru', 'ja', 'zh'];
    private const TAGS_PER_KEY = [1, 2, 3]; // Random number of tags per key

    public function run(): void
    {
        $startTime = microtime(true);
        $this->command->info('Starting translation seeding...');

        DB::beginTransaction();
        try {
            // Create predefined tags
            $tags = $this->createTags();
            $this->command->info('Created ' . count($tags) . ' tags');

            $recordsCreated = 0;
            $uniqueKeysCount = 0;
            $totalTranslations = 0;
            $totalTagAttachments = 0;

            while ($recordsCreated < self::TOTAL_RECORDS) {
                $batchSize = min(self::BATCH_SIZE, self::TOTAL_RECORDS - $recordsCreated);
                $this->command->info(sprintf(
                    'Processing batch %d-%d of %d...',
                    $recordsCreated + 1,
                    $recordsCreated + $batchSize,
                    self::TOTAL_RECORDS
                ));

                // Create translation keys batch
                $translationKeys = $this->createTranslationKeysBatch($batchSize);
                $uniqueKeysCount += count($translationKeys);

                // Create translations batch
                $translations = $this->createTranslationsBatch($translationKeys);
                $totalTranslations += count($translations);

                // Create tag attachments batch
                $tagAttachments = $this->createTagAttachmentsBatch($translationKeys, $tags);
                $totalTagAttachments += count($tagAttachments);

                $recordsCreated += $batchSize;

                // Log progress and memory usage
                $this->logProgress($recordsCreated, $uniqueKeysCount, $totalTranslations, $totalTagAttachments, $startTime);
            }

            DB::commit();
            $this->logFinalStats($uniqueKeysCount, $totalTranslations, $totalTagAttachments, $startTime);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in translation seeding', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create predefined tags.
     *
     * @return \Illuminate\Support\Collection<int, Tag>
     */
    private function createTags(): \Illuminate\Support\Collection
    {
        $tagNames = [
            'mobile', 'desktop', 'web', 'email', 'notification',
            'error', 'success', 'warning', 'info', 'validation',
            'auth', 'profile', 'settings', 'dashboard', 'admin',
        ];

        return collect($tagNames)->map(function (string $name) {
            return Tag::firstOrCreate(['name' => $name]);
        });
    }

    /**
     * Create a batch of translation keys.
     *
     * @param int $count
     * @return \Illuminate\Support\Collection<int, TranslationKey>
     */
    private function createTranslationKeysBatch(int $count): \Illuminate\Support\Collection
    {
        $keys = collect();
        for ($i = 0; $i < $count; $i++) {
            $keys->push([
                'id' => (string) Str::uuid(),
                'key' => $this->generateUniqueKey(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('translation_keys')->insert($keys->toArray());
        return TranslationKey::whereIn('id', $keys->pluck('id'))->get();
    }

    /**
     * Create translations for a batch of translation keys.
     *
     * @param \Illuminate\Support\Collection<int, TranslationKey> $translationKeys
     * @return \Illuminate\Support\Collection<int, Translation>
     */
    private function createTranslationsBatch(\Illuminate\Support\Collection $translationKeys): \Illuminate\Support\Collection
    {
        $translations = collect();
        
        foreach ($translationKeys as $key) {
            foreach (self::LOCALES as $locale) {
                $translations->push([
                    'id' => (string) Str::uuid(),
                    'translation_key_id' => $key->id,
                    'locale' => $locale,
                    'content' => "{$key->key} in {$locale}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Insert in chunks to manage memory
        $translations->chunk(1000)->each(function ($chunk) {
            DB::table('translations')->insert($chunk->toArray());
        });

        return Translation::whereIn('translation_key_id', $translationKeys->pluck('id'))->get();
    }

    /**
     * Create tag attachments for a batch of translation keys.
     *
     * @param \Illuminate\Support\Collection<int, TranslationKey> $translationKeys
     * @param \Illuminate\Support\Collection<int, Tag> $tags
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function createTagAttachmentsBatch(
        \Illuminate\Support\Collection $translationKeys,
        \Illuminate\Support\Collection $tags
    ): \Illuminate\Support\Collection {
        $attachments = collect();

        foreach ($translationKeys as $key) {
            $numTags = self::TAGS_PER_KEY[array_rand(self::TAGS_PER_KEY)];
            $selectedTags = $tags->random($numTags);

            foreach ($selectedTags as $tag) {
                $attachments->push([
                    'id' => (string) Str::uuid(),
                    'tag_id' => $tag->id,
                    'translation_key_id' => $key->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Insert in chunks to manage memory
        $attachments->chunk(1000)->each(function ($chunk) {
            DB::table('tag_translation_key')->insert($chunk->toArray());
        });

        return $attachments;
    }

    /**
     * Generate a unique translation key.
     */
    private function generateUniqueKey(): string
    {
        $prefixes = ['home', 'auth', 'common', 'errors', 'validation', 'messages'];
        $suffixes = ['title', 'description', 'button', 'label', 'placeholder', 'message'];
        
        do {
            $key = $prefixes[array_rand($prefixes)] . '.' . 
                   $suffixes[array_rand($suffixes)] . '.' . 
                   Str::random(4);
        } while (TranslationKey::where('key', $key)->exists());

        return $key;
    }

    /**
     * Log progress of seeding.
     */
    private function logProgress(
        int $recordsCreated,
        int $uniqueKeysCount,
        int $totalTranslations,
        int $totalTagAttachments,
        float $startTime
    ): void {
        $elapsed = microtime(true) - $startTime;
        $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
        
        $this->command->info(sprintf(
            'Progress: %d/%d keys (%.1f%%), %d translations, %d tag attachments (%.2f seconds, %.2f MB)',
            $recordsCreated,
            self::TOTAL_RECORDS,
            ($recordsCreated / self::TOTAL_RECORDS) * 100,
            $totalTranslations,
            $totalTagAttachments,
            $elapsed,
            $memoryUsage
        ));
    }

    /**
     * Log final statistics.
     */
    private function logFinalStats(
        int $uniqueKeysCount,
        int $totalTranslations,
        int $totalTagAttachments,
        float $startTime
    ): void {
        $elapsed = microtime(true) - $startTime;
        $totalRecords = $uniqueKeysCount + $totalTranslations + $totalTagAttachments;
        
        $this->command->info('Translation seeding completed:');
        $this->command->info(sprintf('- Created %d translation keys', $uniqueKeysCount));
        $this->command->info(sprintf('- Created %d translations', $totalTranslations));
        $this->command->info(sprintf('- Created %d tag attachments', $totalTagAttachments));
        $this->command->info(sprintf('- Total time: %.2f seconds', $elapsed));
        $this->command->info(sprintf('- Average: %.2f records per second', $totalRecords / $elapsed));
        $this->command->info(sprintf('- Peak memory usage: %.2f MB', memory_get_peak_usage(true) / 1024 / 1024));
    }
}
