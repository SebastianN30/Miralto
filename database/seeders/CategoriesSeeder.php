<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Entradas',
                'description' => 'Platos pequeños para abrir el apetito con sabores campestres',
                'is_active' => true,
            ],
            [
                'name' => 'Platos Fuertes',
                'description' => 'Platos principales preparados con ingredientes frescos del campo',
                'is_active' => true,
            ],
            [
                'name' => 'Sopas y Caldos',
                'description' => 'Reconfortantes sopas y caldos con recetas tradicionales de la región',
                'is_active' => true,
            ],
            [
                'name' => 'Parrilla',
                'description' => 'Carnes y chorizos asados en leña con sazón campesino',
                'is_active' => true,
            ],
            [
                'name' => 'Bebidas',
                'description' => 'Jugos naturales, limonadas y bebidas refrescantes de la huerta',
                'is_active' => true,
            ],
            [
                'name' => 'Postres',
                'description' => 'Dulces artesanales elaborados con frutas de temporada',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
