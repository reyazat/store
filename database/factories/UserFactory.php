<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ext_user_id' => (string) Str::uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'avatar_url' => fake()->optional()->imageUrl(200, 200, 'people'),
            'roles' => ['user'],
            'permissions' => [],
            'last_synced_at' => now(),
        ];
    }

    /**
     * Indicate that the user has admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'roles' => ['admin', 'user'],
            'permissions' => ['*'],
        ]);
    }

    /**
     * Indicate that the user has editor role.
     */
    public function editor(): static
    {
        return $this->state(fn (array $attributes) => [
            'roles' => ['editor', 'user'],
            'permissions' => ['content.create', 'content.edit', 'content.delete', 'media.upload'],
        ]);
    }
}
