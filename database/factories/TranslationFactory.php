<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Translation::class;

    /**
     * Available locales for translations.
     *
     * @var array<int, string>
     */
    private const LOCALES = ['en', 'es', 'fr', 'de', 'it', 'pt', 'nl', 'ru', 'ja', 'zh'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'translation_key_id' => TranslationKey::factory(),
            'locale' => self::LOCALES[array_rand(self::LOCALES)],
            'content' => fake()->sentence(),
        ];
    }

    /**
     * Create a translation for a specific locale.
     */
    public function forLocale(string $locale): self
    {
        if (!in_array($locale, self::LOCALES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid locale: %s. Must be one of: %s', $locale, implode(', ', self::LOCALES))
            );
        }

        return $this->state(fn (array $attributes) => ['locale' => $locale]);
    }

    /**
     * Create a translation for a specific translation key.
     */
    public function forTranslationKey(TranslationKey $translationKey): self
    {
        return $this->state(fn (array $attributes) => [
            'translation_key_id' => $translationKey->id,
        ]);
    }
}
