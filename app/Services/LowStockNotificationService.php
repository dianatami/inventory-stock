<?php

namespace App\Services;

use App\Models\LowStockNotification;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class LowStockNotificationService
{
    public function sync(): void
    {
        $lowProducts = Product::where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->get(['id', 'stock', 'minimum_stock']);
        $lowProductIds = $lowProducts->pluck('id');

        DB::transaction(function () use ($lowProducts, $lowProductIds) {
            foreach ($lowProducts as $product) {
                $notification = LowStockNotification::firstOrNew([
                    'product_id' => $product->id,
                ]);

                if (! $notification->exists || $notification->resolved_at !== null) {
                    $notification->notified_at = now();
                    $notification->resolved_at = null;
                }

                $notification->current_stock = $product->stock;
                $notification->minimum_stock = $product->minimum_stock;
                $notification->save();
            }

            LowStockNotification::whereNull('resolved_at')
                ->whereNotIn('product_id', $lowProductIds)
                ->update(['resolved_at' => now()]);
        });
    }
}
