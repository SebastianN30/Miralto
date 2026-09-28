<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockDeductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_paying_an_order_decrements_stock_for_each_item(): void
    {
        $order = Order::factory()->pending()->create(['total' => 0]);

        $productA = Product::factory()->create(['stock' => 20]);
        $productB = Product::factory()->create(['stock' => 10]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $productA->id,
            'quantity' => 3,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $productB->id,
            'quantity' => 2,
        ]);

        $order->update(['status' => 'paid']);

        $this->assertEquals(17, $productA->fresh()->stock);
        $this->assertEquals(8, $productB->fresh()->stock);
    }

    public function test_paying_an_order_decrements_stock_for_two_items_of_the_same_product(): void
    {
        $order = Order::factory()->pending()->create(['total' => 0]);
        $product = Product::factory()->create(['stock' => 20]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        $order->update(['status' => 'paid']);

        $this->assertEquals(13, $product->fresh()->stock);
    }

    public function test_untracked_product_with_null_stock_is_never_altered_when_paid(): void
    {
        $order = Order::factory()->pending()->create(['total' => 0]);
        $product = Product::factory()->create(['stock' => null]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $order->update(['status' => 'paid']);

        $this->assertNull($product->fresh()->stock);
    }

    public function test_paying_with_insufficient_stock_does_not_throw_and_allows_negative_stock(): void
    {
        $order = Order::factory()->pending()->create(['total' => 0]);
        $product = Product::factory()->create(['stock' => 2]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $order->update(['status' => 'paid']);

        $this->assertEquals('paid', $order->fresh()->status);
        $this->assertEquals(-3, $product->fresh()->stock);
    }

    public function test_cancelling_a_paid_order_restores_the_exact_stock_that_was_deducted(): void
    {
        $order = Order::factory()->pending()->create(['total' => 0]);
        $product = Product::factory()->create(['stock' => 20]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $order->update(['status' => 'paid']);
        $this->assertEquals(17, $product->fresh()->stock);

        $order->update(['status' => 'cancelled']);

        $this->assertEquals(20, $product->fresh()->stock);
    }

    public function test_cancelling_a_pending_order_that_was_never_paid_does_not_touch_stock(): void
    {
        $order = Order::factory()->pending()->create(['total' => 0]);
        $product = Product::factory()->create(['stock' => 20]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $order->update(['status' => 'cancelled']);

        $this->assertEquals(20, $product->fresh()->stock);
    }

    public function test_splitting_a_paid_order_does_not_deduct_or_restore_stock(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->pending()->create(['user_id' => $user->id, 'total' => 0]);
        $product = Product::factory()->create(['stock' => 20]);

        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $order->update(['status' => 'paid']);
        $this->assertEquals(15, $product->fresh()->stock);

        $this->actingAs($user)->post("/orders/{$order->id}/split", [
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        // Stock stays exactly as it was after the original payment: split moves
        // OrderItem rows between orders, it is not a new sale/refund event.
        $this->assertEquals(15, $product->fresh()->stock);
    }
}
