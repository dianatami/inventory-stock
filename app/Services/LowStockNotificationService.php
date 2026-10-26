<?php

namespace App\Services;

use App\Models\LowStockNotification;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class LowStockNotificationService
{
    public function sync(): void
    {
        DB::transaction(function () {
            $lowProducts = Product::where('is_active', true)
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->lockForUpdate()
                ->get(['id', 'stock', 'minimum_stock']);
            $lowProductIds = $lowProducts->modelKeys();

            foreach ($lowProducts as $product) {
                $severity = $this->severity((int) $product->stock, (int) $product->minimum_stock);
                $notification = LowStockNotification::where('product_id', $product->id)
                    ->whereNull('resolved_at')
                    ->lockForUpdate()
                    ->first();

                if (! $notification) {
                    $notification = new LowStockNotification([
                        'product_id' => $product->id,
                        'notified_at' => now(),
                    ]);
                } elseif ($notification->severity !== $severity) {
                    $notification->notified_at = now();
                    $notification->read_at = null;
                }

                $notification->current_stock = $product->stock;
                $notification->minimum_stock = $product->minimum_stock;
                $notification->severity = $severity;
                $notification->save();
            }

            $query = LowStockNotification::whereNull('resolved_at');
            if ($lowProductIds !== []) {
                $query->whereNotIn('product_id', $lowProductIds);
            }

            $query
                ->update(['resolved_at' => now()]);
        });
    }

    private function severity(int $stock, int $minimum): string
    {
        if ($stock === 0) {
            return 'out';
        }

        if ($minimum > 1 && $stock <= intdiv($minimum, 2)) {
            return 'critical';
        }

        return 'low';
    }
}
