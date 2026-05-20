<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------ Index

    public function test_index_page_is_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/categories')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('categories/Index'));
    }

    public function test_index_page_redirects_guests(): void
    {
        $this->get('/categories')->assertRedirect('/login');
    }

    public function test_index_includes_products_count_and_stats(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        Product::factory()->count(3)->create(['category_id' => $category->id, 'is_active' => true]);

        $this->actingAs($user)
            ->get('/categories')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.total', 1)
                ->where('stats.active', 1)
                ->where('stats.inactive', 0)
                ->has('categories', 1, fn ($c) => $c->where('products_count', 3)->etc()),
            );
    }

    // ------------------------------------------------------------------ Store

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/categories', ['name' => 'Postres', 'description' => 'Dulces y postres'])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'Postres']);
    }

    public function test_employee_cannot_create_category(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->post('/categories', ['name' => 'Postres'])
            ->assertForbidden();
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Entradas']);

        $this->actingAs($admin)
            ->post('/categories', ['name' => 'Entradas'])
            ->assertSessionHasErrors('name');
    }

    public function test_category_name_is_required(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/categories', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    // ------------------------------------------------------------------ Update

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Bebidas', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch("/categories/{$category->id}", [
                'name' => 'Bebidas y licores',
                'description' => 'Refrescos y bebidas alcohólicas',
                'is_active' => false,
            ])
            ->assertRedirect();

        $category->refresh();
        $this->assertEquals('Bebidas y licores', $category->name);
        $this->assertFalse($category->is_active);
    }

    public function test_employee_cannot_update_category(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $category = Category::factory()->create();

        $this->actingAs($employee)
            ->patch("/categories/{$category->id}", ['name' => 'Nuevo', 'is_active' => true])
            ->assertForbidden();
    }

    public function test_update_name_can_stay_the_same(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Parrilla']);

        $this->actingAs($admin)
            ->patch("/categories/{$category->id}", [
                'name' => 'Parrilla',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'Parrilla']);
    }

    // ------------------------------------------------------------------ Destroy

    public function test_admin_can_delete_empty_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->delete("/categories/{$category->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_category_with_products(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin)
            ->delete("/categories/{$category->id}")
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_employee_cannot_delete_category(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $category = Category::factory()->create();

        $this->actingAs($employee)
            ->delete("/categories/{$category->id}")
            ->assertForbidden();
    }
}
