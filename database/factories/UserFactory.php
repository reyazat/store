<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
            'remember_token' => Str::random(10),
            'is_super_admin' => false,
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
