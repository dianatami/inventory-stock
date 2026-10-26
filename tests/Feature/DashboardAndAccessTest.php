<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Outlet;
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

    public function test_company_logo_appears_for_warehouse_roles_and_login_but_not_outlet_portal(): void
    {
        $logo = asset('images/logo-perusahaan.jpeg');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee($logo)
            ->assertSee('login-company-logo', false);

        $this->assertFileExists(public_path('images/logo-perusahaan.jpeg'));

        foreach (['Manager', 'Admin Prolog', 'Kepala Logistik'] as $role) {
            $user = User::factory()->create([
                'username' => 'logo-'.str_replace(' ', '-', strtolower($role)),
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee($logo)
                ->assertSee('company-sidebar-logo', false);

            if ($role === 'Manager') {
                $this->get(route('dashboard'))
                    ->assertSee('Total Supplier')
                    ->assertSee('Jumlah Kategori');
            } elseif ($role === 'Admin Prolog') {
                $this->get(route('dashboard'))
                    ->assertSee('Pesanan Menunggu')
                    ->assertSee('Penerimaan Menunggu')
                    ->assertDontSee('Total Supplier');
            } else {
                $this->get(route('dashboard'))
                    ->assertSee('Retur Menunggu')
                    ->assertSee('Pengajuan Retur yang Perlu Dipantau')
                    ->assertDontSee('Grafik Transaksi Bulanan');
            }
        }

        $outlet = Outlet::create([
            'code' => 'LOGO-OUTLET',
            'name' => 'Outlet Tanpa Logo',
            'is_active' => true,
        ]);
        $outletUser = User::factory()->create([
            'username' => 'logo-outlet',
            'role' => 'User Outlet',
            'outlet_id' => $outlet->id,
            'is_active' => true,
        ]);

        $this->actingAs($outletUser)
            ->get(route('outlet.dashboard'))
            ->assertOk()
            ->assertDontSee($logo)
            ->assertDontSee('company-sidebar-logo', false)
            ->assertDontSee('login-company-logo', false);
    }

    public function test_home_alias_redirects_each_authenticated_role_to_its_dashboard(): void
    {
        $this->get('/home')->assertRedirect(route('login'));

        $manager = User::factory()->create([
            'username' => 'home-manager',
            'role' => 'Manager',
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->get('/home')
            ->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('login'))->assertRedirect(route('dashboard'));

        $outlet = Outlet::create([
            'code' => 'HOME-OUTLET',
            'name' => 'Outlet Home',
            'is_active' => true,
        ]);
        $outletUser = User::factory()->create([
            'username' => 'home-outlet',
            'role' => 'User Outlet',
            'outlet_id' => $outlet->id,
            'is_active' => true,
        ]);

        $this->actingAs($outletUser)
            ->get('/home')
            ->assertRedirect(route('outlet.dashboard'));
        $this->get(route('outlet.dashboard'))->assertOk();
        $this->get(route('login'))->assertRedirect(route('outlet.dashboard'));
    }

    public function test_manager_can_log_in_with_username_and_password(): void
    {
        User::factory()->create([
            'username' => 'omprof0001',
            'role' => 'Manager',
            'is_active' => true,
            'password' => Hash::make('Manager123!'),
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Persediaan terpantau. Distribusi terkendali.')
            ->assertSee('data-password-toggle', false);

        $this->post(route('login.process'), [
            'username' => 'omprof0001',
            'password' => 'Manager123!',
        ])
            ->assertRedirect('/dashboard')
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }
}
