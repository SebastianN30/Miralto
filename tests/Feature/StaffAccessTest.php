<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Printer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(string $role, array $attrs = []): User
    {
        return User::factory()->create([
            'role' => $role,
            'username' => $role.'.demo',
            'email' => $role.'.demo@miralto.local',
            ...$attrs,
        ]);
    }

    // ------------------------------------------------------------------ Login

    public function test_can_login_with_username_and_lands_on_role_home(): void
    {
        $this->staffUser('waiter');
        $this->staffUser('cook');

        $this->post(route('login.store'), ['email' => 'waiter.demo', 'password' => 'password'])
            ->assertRedirect('/waiter');
        $this->post(route('logout'));

        $this->post(route('login.store'), ['email' => 'COOK.demo', 'password' => 'password'])
            ->assertRedirect('/kitchen');
    }

    public function test_admin_still_logs_in_with_email_and_lands_on_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->staffUser('waiter', ['is_active' => false]);

        $this->post(route('login.store'), ['email' => 'waiter.demo', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->staffUser('waiter');

        $this->post(route('login.store'), ['email' => 'waiter.demo', 'password' => 'nope'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ------------------------------------------------------------------ Zones

    public function test_waiter_can_use_waiter_mode_but_not_the_panel_or_kitchen(): void
    {
        $waiter = $this->staffUser('waiter');

        $this->actingAs($waiter)->get('/waiter')->assertOk();
        $this->actingAs($waiter)->get('/waiter/create')->assertOk();

        foreach (['/dashboard', '/orders', '/products', '/cash', '/employees', '/sales'] as $url) {
            $this->actingAs($waiter)->get($url)->assertRedirect('/waiter');
        }
        $this->actingAs($waiter)->get('/kitchen')->assertRedirect('/waiter');
    }

    public function test_cook_can_use_kitchen_but_not_the_panel_or_waiter_mode(): void
    {
        $cook = $this->staffUser('cook');

        $this->actingAs($cook)->get('/kitchen')->assertOk();

        foreach (['/dashboard', '/orders', '/employees', '/waiter'] as $url) {
            $this->actingAs($cook)->get($url)->assertRedirect('/kitchen');
        }
    }

    public function test_admin_and_employee_roles_keep_full_access(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->create(['role' => 'employee'])] as $user) {
            $this->actingAs($user)->get('/dashboard')->assertOk();
            $this->actingAs($user)->get('/waiter')->assertOk();
            $this->actingAs($user)->get('/kitchen')->assertOk();
        }
    }

    // ------------------------------------------------------------------ Employees → users

    public function test_admin_creates_waiter_employee_with_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/employees', [
            'name' => 'Juan Pérez',
            'role' => 'waiter',
            'has_access' => true,
            'username' => 'Juan.Perez',
            'password' => 'clave-segura-1',
        ])->assertRedirect();

        $employee = Employee::where('name', 'Juan Pérez')->firstOrFail();
        $this->assertNotNull($employee->user);
        $this->assertSame('juan.perez', $employee->user->username);
        $this->assertSame('waiter', $employee->user->role);
        $this->assertTrue($employee->user->is_active);

        auth()->logout();
        $this->post(route('login.store'), ['email' => 'juan.perez', 'password' => 'clave-segura-1'])
            ->assertRedirect('/waiter');
    }

    public function test_access_requires_username_and_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/employees', ['name' => 'Ana', 'role' => 'cook', 'has_access' => true])
            ->assertSessionHasErrors(['username', 'password']);
    }

    public function test_only_waiter_and_cook_roles_can_have_access(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/employees', ['name' => 'Ana', 'role' => 'other', 'has_access' => true, 'username' => 'ana', 'password' => 'clave-segura-1'])
            ->assertSessionHasErrors('has_access');
    }

    public function test_username_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $this->staffUser('waiter');

        $this->actingAs($admin)
            ->post('/employees', ['name' => 'Otro', 'role' => 'waiter', 'has_access' => true, 'username' => 'waiter.demo', 'password' => 'clave-segura-1'])
            ->assertSessionHasErrors('username');
    }

    public function test_employee_without_access_creates_no_user(): void
    {
        $admin = User::factory()->admin()->create();
        $before = User::count();

        $this->actingAs($admin)
            ->post('/employees', ['name' => 'Sin acceso', 'role' => 'cook', 'has_access' => false])
            ->assertRedirect();

        $this->assertSame($before, User::count());
        $this->assertNull(Employee::where('name', 'Sin acceso')->first()->user_id);
    }

    public function test_update_can_reset_password_and_change_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->staffUser('waiter');
        $employee = Employee::factory()->create(['role' => 'waiter', 'user_id' => $user->id]);

        $this->actingAs($admin)->patch("/employees/{$employee->id}", [
            'name' => $employee->name,
            'role' => 'cook',
            'is_active' => true,
            'has_access' => true,
            'username' => 'waiter.demo',
            'password' => 'nueva-clave-99',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('cook', $user->role);
        $this->assertTrue(Hash::check('nueva-clave-99', $user->password));
    }

    public function test_update_without_password_keeps_the_current_one(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->staffUser('waiter');
        $employee = Employee::factory()->create(['role' => 'waiter', 'user_id' => $user->id]);

        $this->actingAs($admin)->patch("/employees/{$employee->id}", [
            'name' => 'Nuevo Nombre',
            'role' => 'waiter',
            'is_active' => true,
            'has_access' => true,
            'username' => 'waiter.demo',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('Nuevo Nombre', $user->name);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_turning_off_access_or_deactivating_employee_blocks_login(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->staffUser('waiter');
        $employee = Employee::factory()->create(['role' => 'waiter', 'user_id' => $user->id]);

        $this->actingAs($admin)->patch("/employees/{$employee->id}", [
            'name' => $employee->name, 'role' => 'waiter', 'is_active' => true, 'has_access' => false,
        ])->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($admin)->patch("/employees/{$employee->id}", [
            'name' => $employee->name, 'role' => 'waiter', 'is_active' => true,
            'has_access' => true, 'username' => 'waiter.demo',
        ])->assertRedirect();
        $this->assertTrue($user->fresh()->is_active);

        $this->actingAs($admin)->patch("/employees/{$employee->id}", [
            'name' => $employee->name, 'role' => 'waiter', 'is_active' => false,
            'has_access' => true, 'username' => 'waiter.demo',
        ])->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_deleting_employee_also_deletes_its_unused_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->staffUser('cook');
        $employee = Employee::factory()->create(['role' => 'cook', 'user_id' => $user->id]);

        $this->actingAs($admin)->delete("/employees/{$employee->id}")->assertRedirect();

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_cannot_delete_employee_whose_user_registered_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->staffUser('waiter');
        Order::factory()->create(['user_id' => $user->id]);
        $employee = Employee::factory()->create(['role' => 'waiter', 'user_id' => $user->id]);

        $this->actingAs($admin)
            ->delete("/employees/{$employee->id}")
            ->assertSessionHasErrors('employee');

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    // ------------------------------------------------------------------ Kitchen

    private function pendingOrderWithItems(int $count = 2, ?Printer $printer = null): Order
    {
        $order = Order::factory()->pending()->create(['user_id' => User::factory()->create()->id]);
        Product::factory()->count($count)->create(['printer_id' => $printer?->id])
            ->each(fn (Product $p) => OrderItem::factory()->create([
                'order_id' => $order->id, 'product_id' => $p->id, 'quantity' => 1,
            ]));

        return $order;
    }

    public function test_kitchen_lists_pending_orders_with_unprepared_items(): void
    {
        $cook = $this->staffUser('cook');
        $pending = $this->pendingOrderWithItems();
        Order::factory()->create(['status' => 'paid', 'user_id' => $cook->id]);

        $this->actingAs($cook)->get('/kitchen')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('kitchen/Index')
                ->has('orders', 1)
                ->where('orders.0.id', $pending->id));
    }

    public function test_cook_can_toggle_an_item_and_order_leaves_queue_when_all_ready(): void
    {
        $cook = $this->staffUser('cook');
        $order = $this->pendingOrderWithItems(1);
        $item = $order->items()->first();

        $this->actingAs($cook)->patch("/kitchen/items/{$item->id}/ready")->assertRedirect();
        $this->assertNotNull($item->fresh()->prepared_at);

        $this->actingAs($cook)->get('/kitchen')->assertInertia(fn ($page) => $page->has('orders', 0));

        $this->actingAs($cook)->patch("/kitchen/items/{$item->id}/ready")->assertRedirect();
        $this->assertNull($item->fresh()->prepared_at);
    }

    public function test_mark_order_ready_can_be_limited_to_one_station(): void
    {
        $cook = $this->staffUser('cook');
        $kitchen = Printer::create(['name' => 'Cocina']);
        $bar = Printer::create(['name' => 'Barra']);

        $order = Order::factory()->pending()->create(['user_id' => $cook->id]);
        $dish = Product::factory()->create(['printer_id' => $kitchen->id]);
        $drink = Product::factory()->create(['printer_id' => $bar->id]);
        $dishItem = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $dish->id]);
        $drinkItem = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $drink->id]);

        $this->actingAs($cook)
            ->patch("/kitchen/orders/{$order->id}/ready", ['printer_id' => $kitchen->id])
            ->assertRedirect();

        $this->assertNotNull($dishItem->fresh()->prepared_at);
        $this->assertNull($drinkItem->fresh()->prepared_at);

        $this->actingAs($cook)->patch("/kitchen/orders/{$order->id}/ready")->assertRedirect();
        $this->assertNotNull($drinkItem->fresh()->prepared_at);
    }

    public function test_cannot_mark_items_of_a_non_pending_order(): void
    {
        $cook = $this->staffUser('cook');
        $order = $this->pendingOrderWithItems(1);
        $order->update(['status' => 'paid']);
        $item = $order->items()->first();

        $this->actingAs($cook)
            ->patch("/kitchen/items/{$item->id}/ready")
            ->assertSessionHasErrors('item');
        $this->assertNull($item->fresh()->prepared_at);
    }
}
