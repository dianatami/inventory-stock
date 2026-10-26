<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WarehouseFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_distribution_page_uses_integrated_receipt_workflow(): void
    {
        [$user] = $this->createManagerAndProduct(10);

        $this->actingAs($user)
            ->get(route('distributions.index'))
            ->assertOk()
            ->assertSee('Distribusi Barang')
            ->assertSee('Total kuantitas')
            ->assertSee('Belum ada distribusi yang dikirim.');
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
            ->assertSee('Pusat Notifikasi Sistem')
            ->assertSee('Beras');
    }

    public function test_low_stock_notifications_poll_without_duplicates_and_can_be_read(): void
    {
        [$user, $product] = $this->createManagerAndProduct(4);
        $product->update(['minimum_stock' => 10]);

        $this->actingAs($user)
            ->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('items.0.severity', 'critical')
            ->assertJsonPath('items.0.current_stock', 4);

        $this->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
        $this->assertDatabaseCount('low_stock_notifications', 1);

        $notification = \App\Models\LowStockNotification::firstOrFail();
        $this->patch(route('notifications.read', $notification))
            ->assertNoContent();
        $this->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $product->update(['stock' => 10]);
        $this->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('items.0.severity', 'low');
        $this->post(route('notifications.read-all'))->assertNoContent();
        $this->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $product->update(['stock' => 11]);
        $this->getJson(route('notifications.feed'))->assertOk();
        $this->assertNotNull($notification->fresh()->resolved_at);

        $product->update(['stock' => 4]);
        $this->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
        $this->assertDatabaseCount('low_stock_notifications', 2);
    }

    public function test_scheduled_stock_sync_command_creates_low_stock_notifications(): void
    {
        [, $product] = $this->createManagerAndProduct(4);
        $product->update(['minimum_stock' => 10]);

        $this->artisan('notifications:sync-stock')
            ->expectsOutput('Notifikasi stok berhasil disinkronkan.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('low_stock_notifications', [
            'product_id' => $product->id,
            'severity' => 'critical',
            'current_stock' => 4,
        ]);
    }

    public function test_activity_notifications_are_scoped_filterable_and_marked_read(): void
    {
        [$manager] = $this->createManagerAndProduct(10);
        $notification = SystemNotification::create([
            'event_key' => 'test-return-RT-001',
            'category' => 'return',
            'title' => 'Retur RT-001 menunggu persetujuan',
            'message' => 'Outlet A mengajukan retur dan menunggu persetujuan.',
            'audience' => ['Manager'],
            'reference_type' => 'return',
            'reference_id' => 1,
            'reference_number' => 'RT-001',
        ]);
        SystemNotification::create([
            'event_key' => 'test-order-ORD-001',
            'category' => 'purchase_order',
            'title' => 'Pesanan ORD-001 menunggu persetujuan',
            'message' => 'Pesanan outlet menunggu pemeriksaan.',
            'audience' => ['Manager'],
            'reference_type' => 'order',
            'reference_id' => 1,
            'reference_number' => 'ORD-001',
        ]);

        $this->actingAs($manager)
            ->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonFragment(['category' => 'return', 'reference_number' => 'RT-001']);

        $this->get(route('notifications.low-stock', ['category' => 'return', 'search' => 'RT-001']))
            ->assertOk()
            ->assertSee('Retur RT-001 menunggu persetujuan')
            ->assertDontSee('Pesanan ORD-001 menunggu persetujuan');

        $this->patch(route('notifications.activity.read', $notification))->assertNoContent();
        $this->assertNotNull($notification->fresh()->read_at);
        $this->post(route('notifications.read-all'))->assertNoContent();
        $this->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_activity_notification_read_is_limited_to_its_audience(): void
    {
        [$manager] = $this->createManagerAndProduct(10);
        $notification = SystemNotification::create([
            'event_key' => 'manager-only-event',
            'category' => 'purchase_order',
            'title' => 'Pesanan menunggu persetujuan',
            'message' => 'Pesanan outlet menunggu pemeriksaan.',
            'audience' => ['Manager'],
        ]);
        $otherRole = User::factory()->create([
            'username' => 'admin-prolog-notification',
            'role' => 'Admin Prolog',
            'is_active' => true,
        ]);

        $this->actingAs($otherRole)
            ->patch(route('notifications.activity.read', $notification))
            ->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_product_stock_is_audited_and_cannot_be_changed_from_edit_form(): void
    {
        [$user, $product] = $this->createManagerAndProduct(10);

        $this->actingAs($user)
            ->put(route('products.update', $product), [
                'code' => $product->code,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'supplier_id' => '',
                'unit' => $product->unit,
                'stock' => 99,
                'minimum_stock' => $product->minimum_stock,
                'description' => '',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('stock');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 10]);
        $this->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Gunakan menu Stok Masuk, Stok Keluar, atau Penyesuaian Stok');
    }

    public function test_initial_product_stock_is_recorded_as_a_stock_transaction(): void
    {
        [$user, $product] = $this->createManagerAndProduct(10);

        $this->actingAs($user)
            ->post(route('products.store'), [
                'code' => 'WH-INITIAL-002',
                'name' => 'Minyak Goreng',
                'category_id' => $product->category_id,
                'unit' => 'liter',
                'stock' => 7,
                'minimum_stock' => 2,
                'is_active' => 1,
            ])
            ->assertRedirect(route('products.index'));

        $created = Product::where('code', 'WH-INITIAL-002')->firstOrFail();
        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $created->id,
            'type' => 'in',
            'quantity' => 7,
            'stock_before' => 0,
            'stock_after' => 7,
            'reference_number' => 'INITIAL-WH-INITIAL-002',
        ]);
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
