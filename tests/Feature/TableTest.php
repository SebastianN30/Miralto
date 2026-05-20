<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------ Index

    public function test_index_page_is_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/tables')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('tables/Index'));
    }

    public function test_index_page_redirects_guests(): void
    {
        $this->get('/tables')->assertRedirect('/login');
    }

    public function test_index_includes_stats(): void
    {
        $user = User::factory()->create();
        Table::factory()->count(2)->create(['is_active' => true]);
        Table::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get('/tables')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.total', 3)
                ->where('stats.active', 2)
                ->where('stats.inactive', 1),
            );
    }

    // ------------------------------------------------------------------ Store

    public function test_admin_can_create_table(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/tables', ['name' => 'Mesa 1', 'capacity' => 4, 'zone' => 'Interior'])
            ->assertRedirect();

        $this->assertDatabaseHas('tables', ['name' => 'Mesa 1', 'capacity' => 4, 'zone' => 'Interior']);
    }

    public function test_employee_cannot_create_table(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->post('/tables', ['name' => 'Mesa 2'])
            ->assertForbidden();
    }

    public function test_table_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Table::factory()->create(['name' => 'Mesa 1']);

        $this->actingAs($admin)
            ->post('/tables', ['name' => 'Mesa 1'])
            ->assertSessionHasErrors('name');
    }

    public function test_table_name_is_required(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/tables', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_capacity_must_be_positive_integer(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/tables', ['name' => 'Mesa X', 'capacity' => 0])
            ->assertSessionHasErrors('capacity');
    }

    // ------------------------------------------------------------------ Update

    public function test_admin_can_update_table(): void
    {
        $admin = User::factory()->admin()->create();
        $table = Table::factory()->create(['name' => 'Mesa A', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch("/tables/{$table->id}", [
                'name' => 'Mesa A Premium',
                'capacity' => 6,
                'zone' => 'Terraza',
                'is_active' => false,
            ])
            ->assertRedirect();

        $table->refresh();
        $this->assertEquals('Mesa A Premium', $table->name);
        $this->assertFalse($table->is_active);
    }

    public function test_employee_cannot_update_table(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $table = Table::factory()->create();

        $this->actingAs($employee)
            ->patch("/tables/{$table->id}", ['name' => 'Otro', 'is_active' => true])
            ->assertForbidden();
    }

    public function test_update_name_can_stay_the_same(): void
    {
        $admin = User::factory()->admin()->create();
        $table = Table::factory()->create(['name' => 'Terraza 1']);

        $this->actingAs($admin)
            ->patch("/tables/{$table->id}", ['name' => 'Terraza 1', 'is_active' => true])
            ->assertRedirect();

        $this->assertDatabaseHas('tables', ['name' => 'Terraza 1']);
    }

    // ------------------------------------------------------------------ Destroy

    public function test_admin_can_delete_table_without_pending_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $table = Table::factory()->create();

        $this->actingAs($admin)
            ->delete("/tables/{$table->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('tables', ['id' => $table->id]);
    }

    public function test_admin_cannot_delete_table_with_pending_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $table = Table::factory()->create();
        Order::factory()->create(['table_id' => $table->id, 'status' => 'pending', 'user_id' => $admin->id]);

        $this->actingAs($admin)
            ->delete("/tables/{$table->id}")
            ->assertSessionHasErrors('table');

        $this->assertDatabaseHas('tables', ['id' => $table->id]);
    }

    public function test_employee_cannot_delete_table(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $table = Table::factory()->create();

        $this->actingAs($employee)
            ->delete("/tables/{$table->id}")
            ->assertForbidden();
    }
}
