<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'user_id' => null,
            'order_id' => null,
            'type' => fake()->randomElement(['income', 'expense', 'payment']),
            'amount' => fake()->randomFloat(2, 1000, 200000),
            'description' => fake()->sentence(4),
            'reference' => fake()->optional(0.5)->numerify('REF-######'),
            'transaction_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ];
    }

    public function income(): static
    {
        return $this->state(['type' => 'income']);
    }

    public function expense(): static
    {
        return $this->state(['type' => 'expense']);
    }

    public function payment(): static
    {
        return $this->state(['type' => 'payment']);
    }
}
