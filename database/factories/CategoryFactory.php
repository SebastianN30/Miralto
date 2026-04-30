<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Entradas' => 'Platos pequeños para comenzar la experiencia culinaria',
            'Platos Fuertes' => 'Platos principales preparados con ingredientes frescos del campo',
            'Bebidas' => 'Refrescantes bebidas naturales y tradicionales',
            'Sopas y Caldos' => 'Reconfortantes sopas preparadas con recetas caseras',
            'Postres' => 'Dulces elaborados con frutas y recetas artesanales',
            'Parrilla' => 'Carnes y vegetales asados en leña',
        ];

        $name = fake()->unique()->randomElement(array_keys($categories));

        return [
            'name' => $name,
            'description' => $categories[$name],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
