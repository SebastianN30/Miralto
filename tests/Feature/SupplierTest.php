<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------ Index

    public function test_index_page_is_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/suppliers')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('suppliers/Index'));
    }

    public function test_index_page_redirects_guests(): void
    {
        $this->get('/suppliers')->assertRedirect('/login');
    }

    public function test_index_includes_stats(): void
    {
        $user = User::factory()->create();
        Supplier::factory()->count(3)->create(['is_active' => true]);
        Supplier::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get('/suppliers')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.total', 4)
                ->where('stats.active', 3)
                ->where('stats.inactive', 1),
            );
    }

    // ------------------------------------------------------------------ Store

    public function test_admin_can_create_supplier(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/suppliers', [
                'name' => 'Distribuidora El Campo',
                'contact_name' => 'Carlos López',
                'phone' => '300 123 4567',
                'email' => 'ventas@elcampo.co',
                'notes' => 'Entrega los lunes y jueves',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['name' => 'Distribuidora El Campo']);
    }

    public function test_employee_cannot_create_supplier(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->post('/suppliers', ['name' => 'Proveedor X'])
            ->assertForbidden();
    }

    public function test_supplier_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Supplier::factory()->create(['name' => 'Distribuidora El Campo']);

        $this->actingAs($admin)
            ->post('/suppliers', ['name' => 'Distribuidora El Campo'])
            ->assertSessionHasErrors('name');
    }

    public function test_supplier_name_is_required(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/suppliers', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_supplier_email_must_be_valid(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/suppliers', ['name' => 'Proveedor Y', 'email' => 'no-es-email'])
            ->assertSessionHasErrors('email');
    }

    // ------------------------------------------------------------------ Update

    public function test_admin_can_update_supplier(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create(['name' => 'Proveedor Antiguo', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch("/suppliers/{$supplier->id}", [
                'name' => 'Proveedor Nuevo',
                'contact_name' => 'Ana García',
                'phone' => '',
                'email' => '',
                'notes' => '',
                'is_active' => false,
            ])
            ->assertRedirect();

        $supplier->refresh();
        $this->assertEquals('Proveedor Nuevo', $supplier->name);
        $this->assertFalse($supplier->is_active);
    }

    public function test_employee_cannot_update_supplier(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $supplier = Supplier::factory()->create();

        $this->actingAs($employee)
            ->patch("/suppliers/{$supplier->id}", ['name' => 'Otro', 'is_active' => true])
            ->assertForbidden();
    }

    public function test_update_name_can_stay_the_same(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create(['name' => 'Lácteos del Norte']);

        $this->actingAs($admin)
            ->patch("/suppliers/{$supplier->id}", ['name' => 'Lácteos del Norte', 'is_active' => true])
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['name' => 'Lácteos del Norte']);
    }

    // ------------------------------------------------------------------ Destroy

    public function test_admin_can_delete_supplier(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)
            ->delete("/suppliers/{$supplier->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_employee_cannot_delete_supplier(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $supplier = Supplier::factory()->create();

        $this->actingAs($employee)
            ->delete("/suppliers/{$supplier->id}")
            ->assertForbidden();
    }
}
