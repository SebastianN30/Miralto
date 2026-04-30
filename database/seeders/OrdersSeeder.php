<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $products = Product::all();

        for ($i = 0; $i < 20; $i++) {
            $user = $users->random();
            $status = fake()->randomElement(['pending', 'paid', 'paid', 'paid', 'cancelled']);
            $paymentMethod = $status === 'cancelled' ? null : fake()->randomElement(['cash', 'transfer', 'card']);

            $order = Order::create([
                'user_id' => $user->id,
                'total' => 0,
                'status' => $status,
                'payment_method' => $paymentMethod,
                'notes' => fake()->optional(0.2)->sentence(),
            ]);

            $itemCount = fake()->numberBetween(1, 5);
            $selectedProducts = $products->random($itemCount);
            $total = 0;

            foreach ($selectedProducts as $product) {
                $quantity = fake()->numberBetween(1, 3);
                $price = $product->price;
                $subtotal = round($price * $quantity, 2);
                $total += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total' => $total]);
        }
    }
}
