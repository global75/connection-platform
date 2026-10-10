<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'password'          => Hash::make('password'),
            'role'              => 'job_seeker',
            'is_active'         => true,
            'email_verified_at' => now(),
        ];
    }

    public function employer(): static
    {
        return $this->state(fn () => ['role' => 'employer']);
    }

    public function jobSeeker(): static
    {
        return $this->state(fn () => ['role' => 'job_seeker']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }
}
