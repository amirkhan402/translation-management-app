<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TranslationKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TranslationKey>
 */
class TranslationKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TranslationKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefixes = ['home', 'auth', 'common', 'errors', 'validation', 'messages'];
        $suffixes = ['title', 'description', 'button', 'label', 'placeholder', 'message'];
        
        return [
            'key' => $prefixes[array_rand($prefixes)] . '.' . 
                    $suffixes[array_rand($suffixes)] . '.' . 
                    Str::random(4),
        ];
    }

    /**
     * Ensure the key is unique.
     */
    public function unique(): self
    {
        return $this->state(function (array $attributes) {
            do {
                $key = $this->definition()['key'];
            } while (TranslationKey::where('key', $key)->exists());

            return ['key' => $key];
        });
    }
} 