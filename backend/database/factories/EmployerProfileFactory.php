<?php

namespace Database\Factories;

use App\Models\EmployerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmployerProfile>
 */
class EmployerProfileFactory extends Factory
{
    protected $model = EmployerProfile::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'user_id'              => User::factory()->employer(),
            'company_name'         => $name,
            'company_slug'         => Str::slug($name).'-'.Str::random(5),
            'industry'             => 'Software',
            'headquarters_country' => 'US',
        ];
    }
}
