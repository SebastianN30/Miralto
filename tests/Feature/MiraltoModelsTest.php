<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiraltoModelsTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------ Users

    public function test_user_has_role_and_is_active_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create();

        $this->assertEquals('admin', $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isEmployee());

        $this->assertEquals('employee', $employee->role);
        $this->assertTrue($employee->isEmployee());
        $this->assertTrue($employee->is_active);
    }

    public function test_inactive_user_state(): void
    {
        $user = User::factory()->inactive()->create();

        $this->assertFalse($user->is_active);
    }

    public function test_user_has_orders_relationship(): void
    {
        $user = User::factory()->create();
        $orders = Order::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->orders);
    }

    // --------------------------------------------------------------- Category

    public function test_category_can_be_created(): void
    {
        $category = Category::factory()->create(['name' => 'Entradas', 'is_active' => true]);

        $this->assertDatabaseHas('categories', ['name' => 'Entradas']);
        $this->assertTrue($category->is_active);
    }

    public function test_category_has_products_relationship(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertCount(3, $category->products);
    }

    public function test_category_active_products_excludes_inactive(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $category->id, 'is_active' => true]);
        Product::factory()->inactive()->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->activeProducts);
        $this->assertCount(3, $category->products);
    }

    // ---------------------------------------------------------------- Product

    public function test_product_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($product->category->is($category));
    }

    public function test_product_soft_deletes(): void
    {
        $product = Product::factory()->create();
        $productId = $product->id;

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $productId]);
        $this->assertNull(Product::find($productId));
        $this->assertNotNull(Product::withTrashed()->find($productId));
    }

    public function test_product_margin_percentage_calculates_correctly(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'cost' => 4000]);

        $this->assertEquals(60.0, $product->marginPercentage());
    }

    public function test_product_margin_percentage_returns_null_when_no_cost(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'cost' => null]);

        $this->assertNull($product->marginPercentage());
    }

    // ------------------------------------------------------------------ Order

    public function test_order_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($order->user->is($user));
    }

    public function test_order_status_helpers(): void
    {
        $pending = Order::factory()->pending()->create();
        $paid = Order::factory()->paid()->create();
        $cancelled = Order::factory()->cancelled()->create();

        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isPaid());

        $this->assertTrue($paid->isPaid());
        $this->assertFalse($paid->isCancelled());

        $this->assertTrue($cancelled->isCancelled());
    }

    public function test_order_recalculate_total(): void
    {
        $order = Order::factory()->create(['total' => 0]);
        $product = Product::factory()->create(['price' => 15000]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 15000,
            'subtotal' => 30000,
        ]);

        $order->recalculateTotal();

        $this->assertEquals('30000.00', $order->fresh()->total);
    }

    // ------------------------------------------------------------- OrderItem

    public function test_order_item_belongs_to_order_and_product(): void
    {
        $order = Order::factory()->create();
        $product = Product::factory()->create(['price' => 20000]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 20000,
            'subtotal' => 20000,
        ]);

        $this->assertTrue($item->order->is($order));
        $this->assertTrue($item->product->is($product));
    }

    public function test_order_has_many_items(): void
    {
        $order = Order::factory()->create();
        $products = Product::factory()->count(3)->create();

        foreach ($products as $product) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => $product->price,
                'subtotal' => $product->price,
            ]);
        }

        $this->assertCount(3, $order->items);
    }
}
