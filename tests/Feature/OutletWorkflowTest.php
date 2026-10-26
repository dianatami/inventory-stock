<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Outlet;
use App\Models\OutletOrder;
use App\Models\OutletReceipt;
use App\Models\OutletReturn;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_receipt_stocktake_and_return_update_stocks_once(): void
    {
        [$manager, $outletUser, $outlet, $product] = $this->makeInventory();

        $this->actingAs($outletUser)
            ->get(route('outlet.dashboard'))
            ->assertOk()
            ->assertSee($outlet->name);

        $this->actingAs($outletUser)
            ->post(route('outlet.orders.store'), [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 6],
                ],
            ])
            ->assertRedirect(route('outlet.orders.index'));

        $order = OutletOrder::with('items')->firstOrFail();
        $orderItem = $order->items->firstOrFail();
        $this->assertDatabaseHas('system_notifications', [
            'event_key' => 'order-request:'.$order->id,
            'category' => 'purchase_order',
            'reference_number' => $order->order_number,
        ]);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-order-notification', false)
            ->assertSee($outlet->name);

        $this->actingAs($manager)
            ->patch(route('warehouse.orders.review', $order), [
                'decision' => 'approve',
                'approved' => [$orderItem->id => 5],
            ])
            ->assertSessionHasNoErrors();

        $this->post(route('warehouse.orders.dispatch', $order))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('system_notifications', [
            'event_key' => 'order-dispatch:'.$order->id,
            'category' => 'distribution',
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 15]);
        $this->assertDatabaseHas('stock_mutations', [
            'product_id' => $product->id,
            'outlet_order_item_id' => $orderItem->id,
            'quantity' => 5,
            'from_location' => 'Gudang',
            'to_location' => $outlet->name,
        ]);
        $this->assertDatabaseHas('workflow_status_histories', [
            'subject_type' => OutletOrder::class,
            'subject_id' => $order->id,
            'from_status' => 'approved',
            'to_status' => 'dispatched',
            'user_id' => $manager->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseCount('stock_mutations', 1);
        $this->get(route('distributions.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Dalam pengiriman')
            ->assertSee('Lihat detail');
        $this->assertDatabaseCount('outlet_stocks', 0);

        $this->post(route('warehouse.orders.dispatch', $order))
            ->assertSessionHasErrors('order');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 15]);
        $this->assertDatabaseCount('stock_mutations', 1);

        $this->actingAs($outletUser)
            ->post(route('outlet.orders.receipt', $order), [
                'received' => [$orderItem->id => 4],
                'damaged' => [$orderItem->id => 1],
                'short' => [$orderItem->id => 1],
            ])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('system_notifications', [
            'event_key' => 'receipt-submitted:'.$order->id,
            'category' => 'distribution',
        ]);

        $receipt = OutletReceipt::with('items')->firstOrFail();
        $receiptItem = $receipt->items->firstOrFail();
        $this->assertSame(1, $receiptItem->damaged_quantity);
        $this->assertSame(1, $receiptItem->short_quantity);
        $this->assertDatabaseCount('outlet_stocks', 0);
        $this->actingAs($manager)
            ->post(route('warehouse.receipts.validate', $receipt), [
                'validated' => [$receiptItem->id => 3],
            ])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('system_notifications', [
            'event_key' => 'receipt-validated:'.$receipt->id,
            'category' => 'distribution',
        ]);
        $this->assertDatabaseHas('outlet_stocks', [
            'outlet_id' => $outlet->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->post(route('warehouse.receipts.validate', $receipt), [
            'validated' => [$receiptItem->id => 3],
        ])->assertSessionHasErrors('receipt');
        $this->assertDatabaseHas('outlet_stocks', [
            'outlet_id' => $outlet->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $date = today()->toDateString();
        $this->actingAs($outletUser)
            ->post(route('outlet.stocktake.store'), [
                'stocktake_date' => $date,
                'items' => [$product->id => 2],
            ])
            ->assertRedirect(route('outlet.stocktake', ['date' => $date]));

        $this->assertDatabaseHas('outlet_stocktake_items', [
            'product_id' => $product->id,
            'opening_stock' => 0,
            'received_quantity' => 3,
            'closing_stock' => 2,
            'used_quantity' => 1,
            'discrepancy' => 0,
        ]);

        $this->post(route('outlet.returns.store'), [
            'receipt_id' => $receipt->id,
            'items' => [$receiptItem->id => 1],
            'conditions' => [$receiptItem->id => 'packaging'],
            'reason' => 'Kemasan rusak, isi produk masih layak.',
        ])->assertRedirect(route('outlet.returns.index'));

        $return = OutletReturn::with('items')->firstOrFail();
        $this->assertDatabaseHas('system_notifications', [
            'event_key' => 'return-request:'.$return->id,
            'category' => 'return',
        ]);
        $returnItem = $return->items->firstOrFail();
        $this->actingAs($manager)
            ->patch(route('warehouse.returns.review', $return), [
                'decision' => 'approve',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 15]);
        $this->assertDatabaseHas('outlet_stocks', [
            'outlet_id' => $outlet->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($outletUser)
            ->post(route('outlet.returns.dispatch', $return))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('system_notifications', [
            'event_key' => 'return-dispatch:'.$return->id,
            'category' => 'return',
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 15]);
        $this->assertDatabaseHas('outlet_stocks', [
            'outlet_id' => $outlet->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->post(route('outlet.returns.dispatch', $return))
            ->assertSessionHasErrors('return');

        $this->actingAs($manager);
        $this->post(route('warehouse.returns.verify', $return), [
            'verified' => [$returnItem->id => 1],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('system_notifications', [
            'event_key' => 'return-verified:'.$return->id,
            'category' => 'return',
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 16]);
        $this->assertDatabaseHas('outlet_stocks', [
            'outlet_id' => $outlet->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('workflow_status_histories', [
            'subject_type' => OutletReturn::class,
            'subject_id' => $return->id,
            'from_status' => 'dispatched',
            'to_status' => 'verified',
            'user_id' => $manager->id,
            'quantity' => 1,
        ]);

        $this->post(route('warehouse.returns.verify', $return), [
            'verified' => [$returnItem->id => 1],
        ])->assertSessionHasErrors('verified');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 16]);
        $this->assertDatabaseHas('outlet_stocks', [
            'outlet_id' => $outlet->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_rejected_orders_and_overstock_requests_do_not_change_warehouse_stock(): void
    {
        [$manager, $outletUser, , $product] = $this->makeInventory();

        $this->actingAs($outletUser)
            ->post(route('outlet.orders.store'), [
                'items' => [['product_id' => $product->id, 'quantity' => 21]],
            ])
            ->assertSessionHasErrors('items');
        $this->assertDatabaseCount('outlet_orders', 0);

        $this->post(route('outlet.orders.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 6]],
        ])->assertRedirect(route('outlet.orders.index'));
        $order = OutletOrder::firstOrFail();

        $this->actingAs($manager)
            ->patch(route('warehouse.orders.review', $order), [
                'decision' => 'reject',
                'decision_notes' => 'Stok dialokasikan untuk kebutuhan lain.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 20]);
        $this->assertDatabaseCount('stock_transactions', 0);
        $this->assertDatabaseHas('outlet_orders', ['id' => $order->id, 'status' => 'rejected']);
    }

    public function test_outlet_login_redirects_to_its_own_portal_and_is_not_a_warehouse_user(): void
    {
        [, $outletUser] = $this->makeInventory();

        $this->post(route('login.process'), [
            'username' => $outletUser->username,
            'password' => 'OutletPass123!',
        ])->assertRedirect(route('outlet.dashboard'));

        $this->actingAs($outletUser)
            ->get(route('dashboard'))
            ->assertForbidden();

        $this->get(route('users.index'))->assertForbidden();

        $this->actingAs($outletUser);
        foreach ([
            'outlet.stocks.index',
            'outlet.orders.create',
            'outlet.returns.create',
            'outlet.stocktake',
            'outlet.usage.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
        $this->get(route('notifications.low-stock'))->assertForbidden();
        $this->getJson(route('notifications.feed'))->assertForbidden();
    }

    private function makeInventory(): array
    {
        $manager = User::factory()->create([
            'username' => 'workflow-manager',
            'role' => 'Manager',
            'is_active' => true,
        ]);
        $outlet = Outlet::create([
            'code' => 'TEST-OUTLET',
            'name' => 'Outlet Uji',
            'is_active' => true,
        ]);
        $outletUser = User::factory()->create([
            'username' => 'workflow-outlet',
            'role' => 'User Outlet',
            'outlet_id' => $outlet->id,
            'is_active' => true,
            'password' => bcrypt('OutletPass123!'),
        ]);
        $category = Category::create(['name' => 'Bahan uji']);
        $product = Product::create([
            'code' => 'WORKFLOW-001',
            'name' => 'Produk Uji',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'stock' => 20,
            'minimum_stock' => 2,
            'is_active' => true,
        ]);

        return [$manager, $outletUser, $outlet, $product];
    }
}
