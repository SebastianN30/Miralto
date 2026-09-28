<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------ Index

    public function test_index_page_is_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->create();
        Employee::factory()->count(2)->create();
        Employee::factory()->inactive()->create();

        $this->actingAs($user)
            ->get('/employees')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('employees/Index')
                ->where('stats.total', 3)
                ->where('stats.active', 2)
                ->where('stats.inactive', 1));
    }

    public function test_index_redirects_guests(): void
    {
        $this->get('/employees')->assertRedirect('/login');
    }

    // ------------------------------------------------------------------ CRUD

    public function test_admin_can_create_employee(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/employees', ['name' => 'Juan Pérez', 'position' => 'Mesero', 'role' => 'other', 'has_access' => false])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', ['name' => 'Juan Pérez', 'position' => 'Mesero', 'is_active' => true]);
    }

    public function test_non_admin_cannot_create_employee(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->actingAs($user)
            ->post('/employees', ['name' => 'Ana'])
            ->assertForbidden();
    }

    public function test_employee_name_is_required_and_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Employee::factory()->create(['name' => 'Ana']);

        $this->actingAs($admin)->post('/employees', ['name' => ''])->assertSessionHasErrors('name');
        $this->actingAs($admin)->post('/employees', ['name' => 'Ana'])->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_employee(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create(['name' => 'Ana']);

        $this->actingAs($admin)
            ->patch("/employees/{$employee->id}", ['name' => 'Ana María', 'position' => 'Cocina', 'role' => 'other', 'has_access' => false, 'is_active' => false])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'name' => 'Ana María', 'is_active' => false]);
    }

    public function test_admin_can_delete_employee_without_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($admin)->delete("/employees/{$employee->id}")->assertRedirect();

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_cannot_delete_employee_with_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create();
        Order::factory()->create(['employee_id' => $employee->id, 'user_id' => $admin->id]);

        $this->actingAs($admin)
            ->delete("/employees/{$employee->id}")
            ->assertSessionHasErrors('employee');

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    // ------------------------------------------------------------------ Orders

    public function test_order_can_be_created_with_employee(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create();
        $product = Product::factory()->create(['stock' => 20]);

        $this->actingAs($admin)
            ->post('/orders', [
                'employee_id' => $employee->id,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['employee_id' => $employee->id]);
    }

    public function test_order_rejects_unknown_employee(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 20]);

        $this->actingAs($admin)
            ->post('/orders', [
                'employee_id' => 9999,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ])
            ->assertSessionHasErrors('employee_id');
    }

    public function test_order_employee_can_be_changed_and_cleared(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create();
        $order = Order::factory()->pending()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->patch("/orders/{$order->id}", ['status' => 'pending', 'employee_id' => $employee->id])
            ->assertRedirect();
        $this->assertSame($employee->id, $order->fresh()->employee_id);

        $this->actingAs($admin)
            ->patch("/orders/{$order->id}", ['status' => 'pending', 'employee_id' => null])
            ->assertRedirect();
        $this->assertNull($order->fresh()->employee_id);
    }

    public function test_update_without_employee_or_table_keys_keeps_them(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create();
        $table = Table::factory()->create(['name' => 'C2']);
        $order = Order::factory()->pending()->create([
            'user_id' => $admin->id,
            'employee_id' => $employee->id,
            'table_id' => $table->id,
            'table_name' => 'C2',
        ]);

        $this->actingAs($admin)
            ->patch("/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame($employee->id, $order->employee_id);
        $this->assertSame($table->id, $order->table_id);
        $this->assertSame('C2', $order->table_name);
    }

    public function test_orders_index_filters_by_employee(): void
    {
        $admin = User::factory()->admin()->create();
        $ana = Employee::factory()->create();
        $luis = Employee::factory()->create();
        Order::factory()->create(['user_id' => $admin->id, 'employee_id' => $ana->id]);
        Order::factory()->create(['user_id' => $admin->id, 'employee_id' => $luis->id]);
        Order::factory()->create(['user_id' => $admin->id, 'employee_id' => null]);

        $this->actingAs($admin)
            ->get('/orders?employee=any')
            ->assertInertia(fn ($page) => $page->has('orders.data', 2));

        $this->actingAs($admin)
            ->get("/orders?employee={$ana->id}")
            ->assertInertia(fn ($page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.employee_id', $ana->id));
    }

    public function test_employee_filter_is_not_overridden_by_search(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Test']);
        $ana = Employee::factory()->create();
        Order::factory()->create(['user_id' => $admin->id, 'employee_id' => $ana->id]);
        Order::factory()->create(['user_id' => $admin->id, 'employee_id' => null]);

        $this->actingAs($admin)
            ->get('/orders?employee=any&search=Admin')
            ->assertInertia(fn ($page) => $page->has('orders.data', 1));
    }
}
