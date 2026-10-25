<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardAndAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_live_inventory_metrics(): void
    {
        $user = User::factory()->create([
            'username' => 'manager01',
            'role' => 'Manager',
            'is_active' => true,
        ]);
        $category = Category::create(['name' => 'Bahan baku']);

        Product::create([
            'code' => 'BRG-001',
            'name' => 'Tepung',
            'category_id' => $category->id,
            'unit' => 'kg',
            'stock' => 4,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Produk')
            ->assertSee('Total Stok')
            ->assertSee('Notifikasi Stok Menipis')
            ->assertSee('Perhatian: stok menipis')
            ->assertSee('Tepung')
            ->assertSee('1');
    }

    public function test_admin_prolog_can_access_stock_transactions_but_not_product_management(): void
    {
        $user = User::factory()->create([
            'username' => 'adminprolog',
            'role' => 'Admin Prolog',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('stock-in.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertForbidden();
    }

    public function test_stock_transaction_pages_require_authentication(): void
    {
        $this->get(route('stock-in.index'))
            ->assertRedirect(route('login'));
    }

    public function test_manager_can_log_in_with_username_and_password(): void
    {
        User::factory()->create([
            'username' => 'omprof0001',
            'role' => 'Manager',
            'is_active' => true,
            'password' => Hash::make('Manager123!'),
        ]);

        $this->post(route('login.process'), [
            'username' => 'omprof0001',
            'password' => 'Manager123!',
        ])
            ->assertRedirect('/dashboard')
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }
}
