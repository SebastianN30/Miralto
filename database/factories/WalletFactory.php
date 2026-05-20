<?php

namespace Database\Factories;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['NUBANK Llave Principal', 'Daviplata', 'Nequi', 'Otro']),
            'type' => fake()->randomElement(['nubank', 'daviplata', 'nequi', 'other']),
            'account_identifier' => fake()->numerify('3##-###-####'),
            'initial_balance' => fake()->randomFloat(2, 0, 500000),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function nubank(): static
    {
        return $this->state(['name' => 'NUBANK Llave Principal', 'type' => 'nubank']);
    }

    public function nequi(): static
    {
        return $this->state(['name' => 'Nequi', 'type' => 'nequi']);
    }

    public function daviplata(): static
    {
        return $this->state(['name' => 'Daviplata', 'type' => 'daviplata']);
    }
}
