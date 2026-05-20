<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailySalesTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------ Page access

    public function test_daily_sales_page_is_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/sales')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('cash/DailySales'));
    }

    public function test_daily_sales_page_redirects_guests(): void
    {
        $this->get('/sales')->assertRedirect('/login');
    }

    // ------------------------------------------------------------------ Stats calculation

    public function test_daily_sales_stats_aggregate_payment_methods_correctly(): void
    {
        $user = User::factory()->create();

        // Cash order: 30,000
        Order::factory()->paid()->create([
            'user_id' => $user->id,
            'total' => 30_000,
            'payment_method' => 'cash',
            'updated_at' => now(),
        ]);

        // Transfer order: 50,000
        Order::factory()->paid()->create([
            'user_id' => $user->id,
            'total' => 50_000,
            'payment_method' => 'transfer',
            'updated_at' => now(),
        ]);

        // Card order: 20,000
        Order::factory()->paid()->create([
            'user_id' => $user->id,
            'total' => 20_000,
            'payment_method' => 'card',
            'updated_at' => now(),
        ]);

        // Yesterday's order — should NOT be counted
        Order::factory()->paid()->create([
            'user_id' => $user->id,
            'total' => 999_000,
            'payment_method' => 'cash',
            'updated_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get('/sales');

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('stats.by_cash', 30_000)
            ->where('stats.by_transfer', 50_000)
            ->where('stats.by_card', 20_000)
            ->where('stats.grand_total', 100_000)
            ->where('stats.orders_count', 3),
        );
    }

    public function test_daily_sales_service_charge_totals_are_summed(): void
    {
        $user = User::factory()->create();

        Order::factory()->paid()->create([
            'user_id' => $user->id,
            'total' => 110_000,
            'payment_method' => 'cash',
            'service_charge' => true,
            'service_charge_amount' => 10_000,
            'updated_at' => now(),
        ]);

        Order::factory()->paid()->create([
            'user_id' => $user->id,
            'total' => 55_000,
            'payment_method' => 'cash',
            'service_charge' => true,
            'service_charge_amount' => 5_000,
            'updated_at' => now(),
        ]);

        Order::factory()->paid()->create([
            'user_id' => $user->id,
            'total' => 80_000,
            'payment_method' => 'cash',
            'service_charge' => false,
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/sales');

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('stats.service_total', 15_000)
            ->where('stats.service_count', 2)
            ->where('stats.service_avg', 7_500),
        );
    }

    // ------------------------------------------------------------------ Admin restriction

    public function test_employee_cannot_edit_paid_orders(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $order = Order::factory()->paid()->create(['user_id' => $employee->id]);

        $this->actingAs($employee)
            ->patch("/orders/{$order->id}", [
                'status' => 'paid',
                'payment_method' => 'cash',
                'security_key' => 'MiraltoSTP',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_edit_paid_orders_with_correct_key(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->paid()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->patch("/orders/{$order->id}", [
                'status' => 'paid',
                'payment_method' => 'cash',
                'security_key' => 'MiraltoSTP',
            ])
            ->assertRedirect();
    }

    // ------------------------------------------------------------------ Wallet reversal

    public function test_wallet_transactions_are_replaced_when_paid_order_payment_is_updated(): void
    {
        $admin = User::factory()->admin()->create();
        $wallet1 = Wallet::factory()->create(['is_active' => true]);
        $wallet2 = Wallet::factory()->create(['is_active' => true]);

        $order = Order::factory()->paid()->create([
            'user_id' => $admin->id,
            'total' => 50_000,
            'payment_method' => 'transfer',
        ]);

        // Pre-existing wallet transaction for this order
        WalletTransaction::create([
            'wallet_id' => $wallet1->id,
            'user_id' => $admin->id,
            'order_id' => $order->id,
            'type' => 'payment',
            'amount' => 50_000,
            'description' => "Pago orden #{$order->id}",
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertEquals(1, WalletTransaction::where('order_id', $order->id)->count());

        // Admin changes the payment method and assigns a different wallet
        $this->actingAs($admin)
            ->patch("/orders/{$order->id}", [
                'status' => 'paid',
                'payment_method' => 'transfer',
                'payment_method_2' => null,
                'security_key' => 'MiraltoSTP',
                'wallet_id_1' => $wallet2->id,
            ])
            ->assertRedirect();

        // Old transaction deleted, new one created for wallet2
        $this->assertEquals(0, WalletTransaction::where('order_id', $order->id)->where('wallet_id', $wallet1->id)->count());
        $this->assertEquals(1, WalletTransaction::where('order_id', $order->id)->where('wallet_id', $wallet2->id)->count());
    }

    public function test_wallet_transactions_are_cleared_when_payment_method_changes_from_transfer(): void
    {
        $admin = User::factory()->admin()->create();
        $wallet = Wallet::factory()->create(['is_active' => true]);

        $order = Order::factory()->paid()->create([
            'user_id' => $admin->id,
            'total' => 50_000,
            'payment_method' => 'transfer',
        ]);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $admin->id,
            'order_id' => $order->id,
            'type' => 'payment',
            'amount' => 50_000,
            'description' => "Pago orden #{$order->id}",
            'transaction_date' => now()->toDateString(),
        ]);

        // Admin changes to cash (no wallet_id_1 sent)
        $this->actingAs($admin)
            ->patch("/orders/{$order->id}", [
                'status' => 'paid',
                'payment_method' => 'cash',
                'security_key' => 'MiraltoSTP',
            ])
            ->assertRedirect();

        // Old transaction should be gone, no new one created (cash has no wallet)
        $this->assertEquals(0, WalletTransaction::where('order_id', $order->id)->count());
    }
}
