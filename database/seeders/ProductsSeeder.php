<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        $entradas = Category::where('name', 'Entradas')->first();
        $platos = Category::where('name', 'Platos Fuertes')->first();
        $sopas = Category::where('name', 'Sopas y Caldos')->first();
        $parrilla = Category::where('name', 'Parrilla')->first();
        $bebidas = Category::where('name', 'Bebidas')->first();
        $postres = Category::where('name', 'Postres')->first();

        $products = [
            // Entradas
            [
                'category_id' => $entradas->id,
                'name' => 'Patacones con Hogao',
                'description' => 'Platano verde frito acompanado de hogao casero y queso blanco',
                'price' => 12000,
                'cost' => 4500,
                'is_active' => true,
            ],
            [
                'category_id' => $entradas->id,
                'name' => 'Empanadas de Pipian',
                'description' => 'Tres empanadas de maiz rellenas de pipian con papa criolla (x3)',
                'price' => 9000,
                'cost' => 3000,
                'is_active' => true,
            ],
            [
                'category_id' => $entradas->id,
                'name' => 'Chicharron Crocante',
                'description' => 'Chicharron de cerdo frito y crocante con limon y aji',
                'price' => 15000,
                'cost' => 6000,
                'is_active' => true,
            ],

            // Platos Fuertes
            [
                'category_id' => $platos->id,
                'name' => 'Bandeja Paisa Miralto',
                'description' => 'Bandeja completa con frijoles, arroz, chicharron, chorizo, huevo, aguacate y arepa',
                'price' => 35000,
                'cost' => 14000,
                'is_active' => true,
            ],
            [
                'category_id' => $platos->id,
                'name' => 'Trucha al Limon',
                'description' => 'Trucha fresca al horno con limon, mantequilla y hierbas aromaticas. Acompanada de ensalada y arroz',
                'price' => 38000,
                'cost' => 16000,
                'is_active' => true,
            ],
            [
                'category_id' => $platos->id,
                'name' => 'Pollo Campesino',
                'description' => 'Pechuga de pollo marinada con especias campestres, acompanada de papa criolla, ensalada y arroz',
                'price' => 28000,
                'cost' => 11000,
                'is_active' => true,
            ],
            [
                'category_id' => $platos->id,
                'name' => 'Carne Asada con Papas',
                'description' => 'Punta de anca a la plancha con papas doradas, ensalada y arepa de choclo',
                'price' => 42000,
                'cost' => 18000,
                'is_active' => true,
            ],

            // Sopas y Caldos
            [
                'category_id' => $sopas->id,
                'name' => 'Sancocho Campesino',
                'description' => 'Sancocho de gallina criolla con papa, yuca, mazorca y cilantro. Incluye arroz y aguacate',
                'price' => 32000,
                'cost' => 13000,
                'is_active' => true,
            ],
            [
                'category_id' => $sopas->id,
                'name' => 'Caldo de Costilla',
                'description' => 'Caldo reconfortante de costilla de res con papa, cilantro y limon',
                'price' => 18000,
                'cost' => 7000,
                'is_active' => true,
            ],
            [
                'category_id' => $sopas->id,
                'name' => 'Sopa de Lentejas',
                'description' => 'Sopa casera de lentejas con verduras frescas y chorizo ahumado',
                'price' => 16000,
                'cost' => 5500,
                'is_active' => true,
            ],

            // Parrilla
            [
                'category_id' => $parrilla->id,
                'name' => 'Chuleta de Cerdo a la Brasa',
                'description' => 'Chuleta de cerdo marinada y asada en lena. Acompanada de yuca frita y chimichurri',
                'price' => 40000,
                'cost' => 17000,
                'is_active' => true,
            ],
            [
                'category_id' => $parrilla->id,
                'name' => 'Chorizo Campesino (x3)',
                'description' => 'Tres chorizos artesanales asados en lena con arepa y hogao',
                'price' => 22000,
                'cost' => 9000,
                'is_active' => true,
            ],
            [
                'category_id' => $parrilla->id,
                'name' => 'Costillas BBQ',
                'description' => 'Costillas de cerdo con salsa BBQ artesanal, papa al horno y ensalada coleslaw',
                'price' => 48000,
                'cost' => 20000,
                'is_active' => true,
            ],

            // Bebidas
            [
                'category_id' => $bebidas->id,
                'name' => 'Jugo Natural Personal',
                'description' => 'Jugo de frutas de temporada: lulo, mora, maracuya, guanabana o guayaba',
                'price' => 8000,
                'cost' => 2500,
                'is_active' => true,
            ],
            [
                'category_id' => $bebidas->id,
                'name' => 'Agua de Panela con Limon',
                'description' => 'Refrescante agua de panela con limon y hielo. Bebida tradicional campesina',
                'price' => 5000,
                'cost' => 1200,
                'is_active' => true,
            ],
            [
                'category_id' => $bebidas->id,
                'name' => 'Limonada de Coco',
                'description' => 'Limonada con leche de coco y hielo, cremosa y refrescante',
                'price' => 10000,
                'cost' => 3500,
                'is_active' => true,
            ],
            [
                'category_id' => $bebidas->id,
                'name' => 'Cerveza Nacional',
                'description' => 'Cerveza fria nacional: Aguila, Poker o Club Colombia',
                'price' => 7000,
                'cost' => 3800,
                'is_active' => true,
            ],

            // Postres
            [
                'category_id' => $postres->id,
                'name' => 'Postre de Natas',
                'description' => 'Postre tradicional de natas con arequipe y canela, receta de la abuela',
                'price' => 10000,
                'cost' => 3000,
                'is_active' => true,
            ],
            [
                'category_id' => $postres->id,
                'name' => 'Arroz con Leche',
                'description' => 'Cremoso arroz con leche con canela y ralladura de limon',
                'price' => 8000,
                'cost' => 2200,
                'is_active' => true,
            ],
            [
                'category_id' => $postres->id,
                'name' => 'Torta de Choclo',
                'description' => 'Trozo de torta casera de choclo tierno con arequipe',
                'price' => 9000,
                'cost' => 3000,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
