<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'industry' => fake()->word(),
            'notes' => null,
            'date_joined' => now()->toDateString(),
            'status' => ClientStatus::Active,
            'referral_source' => fake()->randomElement(['referral', 'Google', 'Instagram']),
        ];
    }
}
