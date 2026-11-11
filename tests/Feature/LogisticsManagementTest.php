<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LogisticsRequest;
use App\Models\Outlet;
use App\Models\OutletOrder;
use App\Models\OutletStock;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_logistics_head_has_requested_master_data_and_stock_access_only(): void
    {
        $head = User::factory()->create([
            'role' => 'Kepala Logistik',
            'is_active' => true,
        ]);

        $this->actingAs($head)
            ->get(route('categories.index'))->assertOk();
        $this->get(route('suppliers.index'))->assertOk();
        $this->get(route('products.index'))->assertOk();
        $this->get(route('stock-in.index'))->assertOk();
        $this->get(route('stock-out.index'))->assertOk();
        $this->get('/stock-adjustments')->assertNotFound();
        $this->get(route('reports.stock'))->assertOk();
        $this->get(route('reports.outlets'))->assertOk();
        $this->get(route('logistics-requests.create'))->assertOk();

        $this->get(route('reports.daily'))->assertForbidden();
        $this->get(route('warehouse.orders.index'))->assertOk();
        $this->get(route('warehouse.receipts.index'))
            ->assertOk()
            ->assertSee('Validasi Penerimaan');
        $this->get(route('orders.recap.index'))->assertForbidden();
    }

    public function test_logistics_request_is_scoped_and_never_changes_warehouse_stock(): void
    {
        $head = User::factory()->create(['role' => 'Kepala Logistik', 'is_active' => true]);
        $otherHead = User::factory()->create(['role' => 'Kepala Logistik', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'Admin Prolog', 'is_active' => true]);
        $manager = User::factory()->create(['role' => 'Manager', 'is_active' => true]);
        $supplier = Supplier::create(['name' => 'Supplier Tepung', 'whatsapp' => '08123456789']);
        $category = Category::create(['name' => 'Bahan uji']);
        $product = Product::create([
            'code' => 'REQ-001',
            'name' => 'Tepung',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'unit' => 'kg',
            'stock' => 1,
            'minimum_stock' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($head)
            ->post(route('logistics-requests.store'), [
                'product_id' => $product->id,
                'quantity' => 9,
                'reason' => 'Kebutuhan produksi.',
            ])
            ->assertRedirect(route('logistics-requests.index'));

        $request = LogisticsRequest::firstOrFail();
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertDatabaseHas('logistics_requests', [
            'id' => $request->id,
            'requested_by' => $head->id,
            'product_id' => $product->id,
            'quantity' => 9,
            'unit' => 'kg',
            'status' => 'pending',
        ]);

        $this->actingAs($otherHead)
            ->get(route('logistics-requests.show', $request))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('logistics-requests.index'))
            ->assertOk()
            ->assertSee($request->request_number)
            ->assertSee($head->name);

        $this->get(route('logistics-requests.show', $request))
            ->assertOk()
            ->assertSee('Hubungi Supplier via WhatsApp')
            ->assertSee('wa.me/628123456789');

        $this->actingAs($manager)
            ->get(route('logistics-requests.index'))
            ->assertForbidden();
        $this->get(route('logistics-requests.show', $request))
            ->assertForbidden();
        $this->patch(route('logistics-requests.status', $request), [
            'status' => 'processing',
        ])->assertForbidden();
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Permintaan Barang');

        $this->actingAs($admin);
        $this->patch(route('logistics-requests.status', $request), [
            'status' => 'processing',
            'admin_notes' => 'Sedang ditindaklanjuti.',
        ])->assertRedirect();
        $this->assertDatabaseHas('logistics_requests', [
            'id' => $request->id,
            'status' => 'processing',
            'processed_by' => $admin->id,
        ]);

        $this->actingAs($head)
            ->get(route('logistics-requests.show', $request))
            ->assertOk()
            ->assertSee('Sedang ditindaklanjuti.')
            ->assertDontSee('Hubungi Supplier via WhatsApp');
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_logistics_request_only_accepts_products_at_or_below_warehouse_minimum(): void
    {
        $head = User::factory()->create(['role' => 'Kepala Logistik', 'is_active' => true]);
        $category = Category::create(['name' => 'Kategori stok minimum']);
        $lowStockProduct = Product::create([
            'code' => 'LOW-REQ-001',
            'name' => 'Barang menipis',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'stock' => 2,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);
        $safeProduct = Product::create([
            'code' => 'SAFE-REQ-001',
            'name' => 'Barang aman',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'stock' => 8,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($head)
            ->get(route('logistics-requests.create'))
            ->assertOk()
            ->assertSee('Barang menipis')
            ->assertDontSee('Barang aman');

        $this->post(route('logistics-requests.store'), [
            'product_id' => $safeProduct->id,
            'quantity' => 10,
            'reason' => 'Perlu pengisian ulang.',
        ])->assertSessionHasErrors('product_id');

        $this->assertDatabaseMissing('logistics_requests', ['product_id' => $safeProduct->id]);
        $this->assertSame(2, $lowStockProduct->fresh()->stock);
        $this->assertSame(8, $safeProduct->fresh()->stock);
    }

    public function test_manager_recap_filters_and_shows_orders_from_all_outlets(): void
    {
        $manager = User::factory()->create(['role' => 'Manager', 'is_active' => true]);
        $category = Category::create(['name' => 'Bahan rekap']);
        $product = Product::create([
            'code' => 'RECAP-001',
            'name' => 'Mi kering',
            'category_id' => $category->id,
            'unit' => 'pak',
            'stock' => 10,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);
        $firstOutlet = Outlet::create(['code' => 'REC-1', 'name' => 'MieTime Cikampek', 'is_active' => true]);
        $secondOutlet = Outlet::create(['code' => 'REC-2', 'name' => 'MieTime Karawang', 'is_active' => true]);
        $firstUser = User::factory()->create([
            'role' => 'User Outlet',
            'outlet_id' => $firstOutlet->id,
            'is_active' => true,
        ]);
        $secondUser = User::factory()->create([
            'role' => 'User Outlet',
            'outlet_id' => $secondOutlet->id,
            'is_active' => true,
        ]);
        $firstOrder = $this->makeOrder($firstOutlet, $firstUser, $product, 'ORD-REC-001');
        $this->makeOrder($secondOutlet, $secondUser, $product, 'ORD-REC-002');

        $response = $this->actingAs($manager)
            ->get(route('orders.recap.index', ['outlet_id' => $firstOutlet->id]))
            ->assertOk()
            ->assertSee('ORD-REC-001')
            ->assertSee('Mi kering')
            ->assertDontSee('ORD-REC-002');
        $this->assertSame(1, substr_count($response->getContent(), 'ORD-REC-001'));

        $this->get(route('orders.recap.show', $firstOrder))
            ->assertOk()
            ->assertSee('MieTime Cikampek')
            ->assertSee('Mi kering');

        $this->actingAs($firstUser)
            ->get(route('orders.recap.index'))
            ->assertForbidden();

        $this->get(route('outlet.orders.index'))
            ->assertOk()
            ->assertSee('ORD-REC-001')
            ->assertDontSee('ORD-REC-002');
    }

    public function test_outlet_stock_report_summarizes_and_filters_real_outlet_stocks(): void
    {
        $manager = User::factory()->create(['role' => 'Manager', 'is_active' => true]);
        $category = Category::create(['name' => 'Kategori stok outlet']);
        $product = Product::create([
            'code' => 'OUT-STOCK-001',
            'name' => 'Mi outlet',
            'category_id' => $category->id,
            'unit' => 'pak',
            'stock' => 20,
            'minimum_stock' => 3,
            'is_active' => true,
        ]);
        $lowOutlet = Outlet::create(['code' => 'OUT-LOW', 'name' => 'MieTime Cikampek', 'is_active' => true]);
        $safeOutlet = Outlet::create(['code' => 'OUT-SAFE', 'name' => 'MieTime Karawang', 'is_active' => true]);
        OutletStock::create(['outlet_id' => $lowOutlet->id, 'product_id' => $product->id, 'quantity' => 2]);
        OutletStock::create(['outlet_id' => $safeOutlet->id, 'product_id' => $product->id, 'quantity' => 5]);

        $this->actingAs($manager)
            ->get(route('reports.outlets'))
            ->assertOk()
            ->assertDontSee('Cetak Laporan')
            ->assertSee('Total Outlet')
            ->assertSee('Catatan Stok Menipis')
            ->assertSee('MieTime Cikampek')
            ->assertSee('MieTime Karawang')
            ->assertSee('7');

        $this->get(route('reports.outlets', ['search' => 'Cikampek']))
            ->assertOk()
            ->assertSee('MieTime Cikampek')
            ->assertDontSee('MieTime Karawang');

        $this->get(route('reports.outlets', ['status' => 'low']))
            ->assertOk()
            ->assertSee('MieTime Cikampek')
            ->assertDontSee('MieTime Karawang');

        $this->get(route('reports.outlets', ['status' => 'safe']))
            ->assertOk()
            ->assertSee('MieTime Karawang')
            ->assertDontSee('MieTime Cikampek');
    }

    private function makeOrder(Outlet $outlet, User $user, Product $product, string $number): OutletOrder
    {
        $order = OutletOrder::create([
            'order_number' => $number,
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'status' => 'requested',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'requested_quantity' => 3,
        ]);

        return $order;
    }
}
