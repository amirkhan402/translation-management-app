<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $increment = 1;
        return [
            'key' => 'key_' . $increment++,
            'locale' => fake()->randomElement(['en', 'fr', 'es']),
            'value' => fake()->sentence(),
        ];
    }
}
