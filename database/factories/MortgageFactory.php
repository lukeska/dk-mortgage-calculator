<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mortgage>
 */
class MortgageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'property_value' => fake()->numberBetween(2000000, 10000000),
            'downpayment' => fake()->numberBetween(200000, 2000000),
            'ejerudgift' => fake()->numberBetween(2000, 6000),
            'heating' => fake()->numberBetween(500, 2000),
            'water' => fake()->numberBetween(0, 500),
            'repairs' => fake()->numberBetween(0, 1000),
            'rent_expenses' => fake()->numberBetween(10000, 20000),
            'loan_period_fixed' => fake()->randomElement([10, 20, 30]),
            'loan_period_variable' => fake()->randomElement([10, 20, 30]),
            'fixed_mortgage_percentage' => fake()->numberBetween(0, 100),
            'flexible_loan_type' => fake()->randomElement(['F3', 'F5']),
            'with_repayments' => fake()->boolean(),
            'bank_loan_interest' => fake()->randomFloat(2, 4, 8),
            'bank_loan_period' => fake()->numberBetween(5, 15),
            'interest_rate_f3' => fake()->randomFloat(2, 2, 5),
            'interest_rate_f5' => fake()->randomFloat(2, 2, 5),
            'interest_rate_f30' => fake()->randomFloat(2, 3, 6),
            'bidragssats_adjustment' => fake()->randomFloat(2, 0, 50),
            'f30_no_repay' => fake()->numberBetween(85, 95),
            'f30_with_repay' => fake()->numberBetween(90, 100),
            'inflation_ejerudgift' => fake()->randomFloat(2, 1, 4),
            'inflation_heating' => fake()->randomFloat(2, 1, 4),
            'inflation_water' => fake()->randomFloat(2, 1, 4),
            'inflation_repairs' => fake()->randomFloat(2, 1, 4),
            'inflation_rent' => fake()->randomFloat(2, 1, 4),
            'variable_rate_overrides' => null,
        ];
    }
}
