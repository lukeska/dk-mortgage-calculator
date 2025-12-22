<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'currency_code' => strtoupper(fake()->unique()->currencyCode()),
            'currency_name' => fake()->words(2, true),
            'rate' => fake()->randomFloat(8, 0.01, 10),
            'fetched_at' => now(),
        ];
    }
}
