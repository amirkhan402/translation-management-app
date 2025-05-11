<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Translation;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class TranslationSeeder extends Seeder
{
    private array $locales = ['us', 'en', 'au', 'fr', 'de', 'es', 'it', 'pt', 'nl', 'ru'];
    private const BATCH_SIZE = 100; // Reduced batch size
    private const TOTAL_RECORDS = 100000;
    private array $usedWords = [];

    /**
     * Get a unique word combination
     */
    private function getUniqueWord(): string
    {
        $faker = Faker::create();
        do {
            // Combine 2-3 words to create a unique key
            $word = implode('_', [
                $faker->word(),
                $faker->word(),
                rand(1, 999) // Add a number to ensure uniqueness
            ]);
        } while (in_array($word, $this->usedWords));
        
        $this->usedWords[] = $word;
        return $word;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::withoutEvents(function () {
            // Create fixed tags (mobile, desktop, web)
            $tags = collect(['mobile', 'desktop', 'web'])->map(function ($name) {
                return Tag::firstOrCreate(['name' => $name]);
            });

            $this->command->info('Tags created.');

            $this->command->info('Creating translations...');

            $recordsCreated = 0;
            $uniqueKeysCount = 0;

            while ($recordsCreated < self::TOTAL_RECORDS) {
                DB::beginTransaction();
                try {
                    $batch = [];
                    
                    // Create a smaller batch of translations
                    for ($i = 0; $i < self::BATCH_SIZE && $recordsCreated < self::TOTAL_RECORDS; $i++) {
                        $word = $this->getUniqueWord();
                        $uniqueKeysCount++;
                        
                        // Create translations for each locale using the same word
                        foreach ($this->locales as $locale) {
                            $batch[] = [
                                'key' => $word,
                                'locale' => $locale,
                                'value' => $word,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $recordsCreated++;
                        }
                    }

                    // Insert the batch
                    DB::table('translations')->insert($batch);
                    DB::commit();

                    // Attach tags to all translations of each key
                    foreach (array_chunk($batch, count($this->locales)) as $keyGroup) {
                        // Get the key from the first item in the group (all items have the same key)
                        $key = $keyGroup[0]['key'];
                        // Get all translations for this key
                        $translations = Translation::where('key', $key)->get();
                        // Attach random tags to all translations of this key
                        $tagIds = $tags->random(rand(1, 3))->pluck('id')->toArray();
                        foreach ($translations as $translation) {
                            $translation->tags()->syncWithoutDetaching($tagIds);
                        }
                    }

                    $this->command->info("Created {$recordsCreated} translations ({$uniqueKeysCount} unique words) so far...");
                    
                    // Clear memory
                    unset($batch);
                    gc_collect_cycles();
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->command->error("Error occurred: " . $e->getMessage());
                    throw $e;
                }
            }

            $this->command->info('All translations created and tagged successfully.');
        });
    }
}
