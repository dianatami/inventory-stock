<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WarehouseFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_distribution_page_uses_integrated_receipt_workflow(): void
    {
        [$user] = $this->createManagerAndProduct(10);

        $this->actingAs($user)
            ->get(route('distributions.index'))
            ->assertRedirect(route('warehouse.receipts.index'));
    }

    public function test_supplier_uses_whatsapp_without_a_separate_phone_field(): void
    {
        [$user] = $this->createManagerAndProduct(10);

        $this->assertFalse(Schema::hasColumn('suppliers', 'phone'));
        $this->actingAs($user)
            ->get(route('suppliers.create'))
            ->assertOk()
            ->assertSee('Nomor WhatsApp')
            ->assertDontSee('Nomor Telepon');

        $this->post(route('suppliers.store'), [
            'name' => 'Supplier Uji',
            'whatsapp' => '081234567890',
        ])->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Supplier Uji',
            'whatsapp' => '081234567890',
        ]);
    }

    public function test_stock_adjustment_sets_physical_count_and_records_difference(): void
    {
        [$user, $product] = $this->createManagerAndProduct(12);

        $this->actingAs($user)
            ->post(route('stock-adjustments.store'), [
                'product_id' => $product->id,
                'actual_stock' => 9,
                'transaction_date' => now()->format('Y-m-d\TH:i'),
                'notes' => 'Selisih hasil opname',
            ])
            ->assertRedirect(route('stock-adjustments.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 9]);
        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 3,
            'stock_before' => 12,
            'stock_after' => 9,
        ]);
    }

    public function test_statistics_and_low_stock_pages_render_for_manager(): void
    {
        [$user] = $this->createManagerAndProduct(0);

        $this->actingAs($user)
            ->get(route('statistics.index'))
            ->assertOk()
            ->assertSee('Statistik Data')
            ->assertSee('Ringkasan per Kategori');

        $this->get(route('notifications.low-stock'))
            ->assertOk()
            ->assertSee('Notifikasi Stok Menipis')
            ->assertSee('Beras');
    }

    public function test_daily_stock_report_filters_transactions_by_selected_date(): void
    {
        [$user, $product] = $this->createManagerAndProduct(10);
        $reportDate = today()->toDateString();

        StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 8,
            'stock_before' => 10,
            'stock_after' => 18,
            'reference_number' => 'DAILY-IN',
            'transaction_date' => today()->setTime(9, 15),
        ]);
        StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => 'out',
            'quantity' => 3,
            'stock_before' => 18,
            'stock_after' => 15,
            'reference_number' => 'OTHER-DAY',
            'transaction_date' => today()->subDay()->setTime(10, 0),
        ]);

        $this->actingAs($user)
            ->get(route('reports.daily', ['date' => $reportDate]))
            ->assertOk()
            ->assertSee('Laporan Stok Harian')
            ->assertSee('DAILY-IN')
            ->assertSee('Barang Masuk')
            ->assertDontSee('OTHER-DAY');
    }

    public function test_profile_and_password_can_be_updated_with_current_password(): void
    {
        [$user] = $this->createManagerAndProduct(5);

        $this->actingAs($user)
            ->put(route('settings.profile.update'), [
                'name' => 'Manager Updated',
                'username' => 'manager-updated',
                'email' => 'manager-updated@example.test',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Manager Updated',
            'username' => 'manager-updated',
        ]);

        $this->put(route('settings.password.update'), [
            'current_password' => 'Password123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_only_manager_can_view_or_submit_password_change(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-password',
            'role' => 'Admin Prolog',
            'is_active' => true,
            'password' => Hash::make('Password123!'),
        ]);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertDontSee('Ubah Password')
            ->assertSee('Pengaturan Akun');

        $this->put(route('settings.password.update'), [
            'current_password' => 'Password123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertForbidden();

        $manager = User::factory()->create([
            'username' => 'manager-password',
            'role' => 'Manager',
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Ubah Password');
    }

    public function test_only_manager_can_access_user_management(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-prolog',
            'role' => 'Admin Prolog',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertDontSee('Manajemen Akun Pengguna')
            ->assertDontSee('Manajemen Pengguna');

        $outlet = User::factory()->create([
            'username' => 'outlet-user',
            'role' => 'User Outlet',
            'is_active' => true,
            'password' => Hash::make('OutletPassword123!'),
        ]);

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', $outlet))->assertForbidden();
        $this->post(route('users.store'), [
            'name' => 'Akun Tidak Diizinkan',
            'username' => 'blocked-account',
            'password' => 'Password123!',
            'role' => 'User Outlet',
        ])->assertForbidden();

        $manager = User::factory()->create([
            'username' => 'another-manager',
            'role' => 'Manager',
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Manajemen Pengguna');
    }

    private function createManagerAndProduct(int $stock): array
    {
        $user = User::factory()->create([
            'username' => 'warehouse-manager',
            'role' => 'Manager',
            'is_active' => true,
            'password' => Hash::make('Password123!'),
        ]);
        $category = Category::create(['name' => 'Bahan pokok']);
        $product = Product::create([
            'code' => 'WH-001',
            'name' => 'Beras',
            'category_id' => $category->id,
            'unit' => 'kg',
            'stock' => $stock,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);

        return [$user, $product];
    }
}
