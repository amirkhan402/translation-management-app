<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Translation;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class TranslationSeeder extends Seeder
{
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

            // Seed 100k fake translations (using TranslationFactory)
            $this->command->info('Creating translations...');
            collect(range(1, 100))->each(function () {
                Translation::factory(1000)->create();
            });
            $this->command->info('All translations created.');
            $this->command->info('Attaching tags to translations...');

            // Attach (random) tags (1-3) to each translation
            Translation::chunk(1000, function ($translations) use ($tags) {
                foreach ($translations as $trans) {
                    $trans->tags()->attach($tags->random(rand(1, 3))->pluck('id')->toArray());
                }
            });

            $this->command->info('Tags attached to translations.');
        });
    }
}
