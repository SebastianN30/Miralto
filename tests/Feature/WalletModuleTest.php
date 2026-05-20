<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletModuleTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------ Model

    public function test_wallet_current_balance_is_initial_plus_inbound_minus_outbound(): void
    {
        $wallet = Wallet::factory()->create(['initial_balance' => 100_000]);

        WalletTransaction::factory()->income()->create(['wallet_id' => $wallet->id, 'amount' => 50_000]);
        WalletTransaction::factory()->payment()->create(['wallet_id' => $wallet->id, 'amount' => 30_000]);
        WalletTransaction::factory()->expense()->create(['wallet_id' => $wallet->id, 'amount' => 20_000]);

        $this->assertEquals(160_000.0, $wallet->currentBalance());
        $this->assertEquals(50_000.0, $wallet->totalIncome());
        $this->assertEquals(30_000.0, $wallet->totalPayments());
        $this->assertEquals(20_000.0, $wallet->totalExpenses());
        $this->assertEquals(80_000.0, $wallet->totalInbound());
        $this->assertEquals(20_000.0, $wallet->totalOutbound());
    }

    public function test_wallet_transaction_inbound_outbound_helpers(): void
    {
        $wallet = Wallet::factory()->create();

        $income = WalletTransaction::factory()->income()->create(['wallet_id' => $wallet->id]);
        $payment = WalletTransaction::factory()->payment()->create(['wallet_id' => $wallet->id]);
        $expense = WalletTransaction::factory()->expense()->create(['wallet_id' => $wallet->id]);

        $this->assertTrue($income->isInbound());
        $this->assertFalse($income->isOutbound());

        $this->assertTrue($payment->isInbound());
        $this->assertFalse($payment->isOutbound());

        $this->assertTrue($expense->isOutbound());
        $this->assertFalse($expense->isInbound());
    }

    public function test_wallet_active_scope(): void
    {
        Wallet::factory()->create(['is_active' => true]);
        Wallet::factory()->inactive()->create();

        $this->assertEquals(1, Wallet::active()->count());
    }

    // ------------------------------------------------------------------ HTTP – index

    public function test_authenticated_user_can_view_wallets_index(): void
    {
        $user = User::factory()->create();
        Wallet::factory(3)->create();

        $response = $this->actingAs($user)->get('/wallets');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('wallets/Index')
            ->has('wallets', 3)
            ->has('stats')
        );
    }

    public function test_unauthenticated_user_is_redirected_from_wallets(): void
    {
        $response = $this->get('/wallets');

        $response->assertRedirect('/login');
    }

    // ------------------------------------------------------------------ HTTP – store

    public function test_authenticated_user_can_create_wallet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/wallets', [
            'name' => 'Nequi Principal',
            'type' => 'nequi',
            'account_identifier' => '3101234567',
            'initial_balance' => 50_000,
            'notes' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('wallets', [
            'name' => 'Nequi Principal',
            'type' => 'nequi',
        ]);
    }

    public function test_wallet_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/wallets', []);

        $response->assertSessionHasErrors(['name', 'type', 'initial_balance']);
    }

    // ------------------------------------------------------------------ HTTP – show

    public function test_authenticated_user_can_view_wallet_detail(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create();
        WalletTransaction::factory(2)->create(['wallet_id' => $wallet->id]);

        $response = $this->actingAs($user)->get("/wallets/{$wallet->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('wallets/Show')
            ->has('wallet')
            ->has('totals')
        );
    }

    // ------------------------------------------------------------------ HTTP – update

    public function test_authenticated_user_can_update_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['name' => 'Antigua']);

        $response = $this->actingAs($user)->patch("/wallets/{$wallet->id}", [
            'name' => 'Nueva',
            'type' => 'nequi',
            'account_identifier' => null,
            'initial_balance' => 0,
            'is_active' => true,
            'notes' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'name' => 'Nueva']);
    }

    // ------------------------------------------------------------------ HTTP – transactions

    public function test_authenticated_user_can_store_transaction(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create();

        $response = $this->actingAs($user)->post("/wallets/{$wallet->id}/transactions", [
            'type' => 'payment',
            'amount' => 25_000,
            'description' => 'Pago mesa 3',
            'reference' => 'REF-001',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'payment',
            'amount' => 25_000,
        ]);
    }

    public function test_cannot_add_transaction_to_inactive_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->inactive()->create();

        $response = $this->actingAs($user)->post("/wallets/{$wallet->id}/transactions", [
            'type' => 'payment',
            'amount' => 10_000,
            'description' => 'Prueba',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_transaction_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create();

        $response = $this->actingAs($user)->post("/wallets/{$wallet->id}/transactions", []);

        $response->assertSessionHasErrors(['type', 'amount', 'description', 'transaction_date']);
    }

    public function test_admin_can_delete_transaction(): void
    {
        $admin = User::factory()->admin()->create();
        $tx = WalletTransaction::factory()->create();

        $response = $this->actingAs($admin)->delete("/wallets/transactions/{$tx->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('wallet_transactions', ['id' => $tx->id]);
    }

    public function test_employee_cannot_delete_transaction(): void
    {
        $employee = User::factory()->create();
        $tx = WalletTransaction::factory()->create();

        $response = $this->actingAs($employee)->delete("/wallets/transactions/{$tx->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('wallet_transactions', ['id' => $tx->id]);
    }
}
