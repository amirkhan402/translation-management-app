<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tag::class;

    /**
     * Predefined tag names to ensure consistency.
     *
     * @var array<int, string>
     */
    private const PREDEFINED_TAGS = [
        'mobile', 'desktop', 'web', 'email', 'notification',
        'error', 'success', 'warning', 'info', 'validation',
        'auth', 'profile', 'settings', 'dashboard', 'admin',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => self::PREDEFINED_TAGS[array_rand(self::PREDEFINED_TAGS)],
        ];
    }

    /**
     * Create a tag with a specific name.
     *
     * @throws \InvalidArgumentException
     */
    public function withName(string $name): self
    {
        if (strlen($name) > 50) {
            throw new \InvalidArgumentException('Tag name must not exceed 50 characters');
        }

        return $this->state(fn (array $attributes) => ['name' => $name]);
    }
}
