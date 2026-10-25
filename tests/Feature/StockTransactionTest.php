<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_updates_product_and_returns_to_stock_in_history(): void
    {
        [$user, $product] = $this->createUserAndProduct(10);

        $this->actingAs($user)
            ->post(route('stock-in.store'), [
                'product_id' => $product->id,
                'quantity' => 5,
                'transaction_date' => now()->format('Y-m-d\TH:i'),
                'reference_number' => 'IN-001',
            ])
            ->assertRedirect(route('stock-in.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 15,
        ]);

        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 5,
            'stock_before' => 10,
            'stock_after' => 15,
        ]);
    }

    public function test_stock_out_updates_product_and_returns_to_stock_out_history(): void
    {
        [$user, $product] = $this->createUserAndProduct(10);

        $this->actingAs($user)
            ->post(route('stock-out.store'), [
                'product_id' => $product->id,
                'quantity' => 4,
                'transaction_date' => now()->format('Y-m-d\TH:i'),
                'reference_number' => 'OUT-001',
            ])
            ->assertRedirect(route('stock-out.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 6,
        ]);

        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => 'out',
            'quantity' => 4,
            'stock_before' => 10,
            'stock_after' => 6,
        ]);
    }

    public function test_stock_out_rejects_quantity_greater_than_available_stock(): void
    {
        [$user, $product] = $this->createUserAndProduct(3);

        $this->actingAs($user)
            ->from(route('stock-out.create'))
            ->post(route('stock-out.store'), [
                'product_id' => $product->id,
                'quantity' => 4,
                'transaction_date' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('stock-out.create'))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);

        $this->assertDatabaseCount('stock_transactions', 0);
    }

    private function createUserAndProduct(int $stock): array
    {
        $user = User::factory()->create([
            'username' => 'stock-manager',
            'role' => 'Manager',
            'is_active' => true,
        ]);
        $category = Category::create(['name' => 'Bahan baku']);
        $product = Product::create([
            'code' => 'STOCK-001',
            'name' => 'Beras',
            'category_id' => $category->id,
            'unit' => 'kg',
            'stock' => $stock,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);

        return [$user, $product];
    }
}
