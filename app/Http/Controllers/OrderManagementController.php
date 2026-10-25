<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\OutletOrder;
use App\Models\OutletReceipt;
use App\Models\OutletStock;
use App\Models\OutletStockTransaction;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = OutletOrder::with(['outlet', 'user', 'items.product', 'receipt'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('warehouse.orders.index', compact('orders'));
    }

    public function review(Request $request, OutletOrder $order)
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'approved' => ['sometimes', 'array'],
            'approved.*' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'decision_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($validated, $request, $order) {
            $order = OutletOrder::with('items')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'requested') {
                throw ValidationException::withMessages([
                    'decision' => 'Pesanan ini sudah diproses sebelumnya.',
                ]);
            }

            if ($validated['decision'] === 'reject') {
                $order->update([
                    'status' => 'rejected',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'decision_notes' => $validated['decision_notes'] ?? null,
                ]);

                return;
            }

            $quantities = $validated['approved'] ?? [];
            $hasQuantity = false;
            foreach ($order->items as $item) {
                $quantity = (int) ($quantities[$item->id] ?? 0);
                if ($quantity > $item->requested_quantity) {
                    throw ValidationException::withMessages([
                        'approved' => 'Jumlah disetujui tidak boleh melebihi jumlah pesanan.',
                    ]);
                }
                if ($quantity > 0) {
                    $hasQuantity = true;
                }
                $item->approved_quantity = $quantity;
                $item->save();
            }

            if (! $hasQuantity) {
                throw ValidationException::withMessages([
                    'approved' => 'Setujui setidaknya satu produk dengan jumlah lebih dari nol.',
                ]);
            }

            $order->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'decision_notes' => $validated['decision_notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Keputusan pesanan berhasil disimpan.');
    }

    public function dispatch(Request $request, OutletOrder $order)
    {
        DB::transaction(function () use ($request, $order) {
            $order = OutletOrder::with(['outlet', 'items.product'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'approved') {
                throw ValidationException::withMessages([
                    'order' => 'Hanya pesanan yang disetujui dan belum dikirim yang dapat didistribusikan.',
                ]);
            }

            foreach ($order->items as $item) {
                $quantity = (int) $item->approved_quantity;
                if ($quantity === 0) {
                    $item->dispatched_quantity = 0;
                    $item->save();

                    continue;
                }

                $product = Product::whereKey($item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if (! $product->is_active || $product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'order' => 'Stok gudang tidak cukup untuk mengirim '
                            .$product->name.'. Distribusi dibatalkan.',
                    ]);
                }

                $stockBefore = $product->stock;
                $product->stock -= $quantity;
                $product->save();
                $item->dispatched_quantity = $quantity;
                $item->save();

                StockTransaction::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $product->stock,
                    'reference_number' => $order->order_number,
                    'destination' => $order->outlet->name,
                    'transaction_date' => now(),
                    'notes' => 'Distribusi untuk '.$order->outlet->name,
                ]);

                StockMutation::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'outlet_id' => $order->outlet_id,
                    'outlet_order_item_id' => $item->id,
                    'quantity' => $quantity,
                    'from_location' => 'Gudang',
                    'to_location' => $order->outlet->name,
                    'status' => 'completed',
                    'mutation_date' => now(),
                    'notes' => 'Distribusi pesanan '.$order->order_number,
                ]);
            }

            $order->update([
                'status' => 'dispatched',
                'dispatched_at' => now(),
            ]);
        });

        return back()->with('success', 'Barang berhasil dikirim dan stok gudang telah diperbarui.');
    }

    public function receipts()
    {
        $receipts = OutletReceipt::with([
            'outlet',
            'order.items.product',
            'items.orderItem.product',
            'items.orderItem',
        ])->latest('submitted_at')->paginate(15);

        $distributions = StockTransaction::with(['product', 'user'])
            ->where('type', 'out')
            ->whereNotNull('destination')
            ->latest('transaction_date')
            ->paginate(10, ['*'], 'distributions_page')
            ->withQueryString();

        return view('warehouse.receipts.index', compact('receipts', 'distributions'));
    }

    public function validateReceipt(Request $request, OutletReceipt $receipt)
    {
        $validated = $request->validate([
            'validated' => ['required', 'array'],
            'validated.*' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($validated, $request, $receipt) {
            $receipt = OutletReceipt::with(['items.orderItem.product', 'order'])
                ->whereKey($receipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($receipt->status !== 'submitted' || $receipt->order->status !== 'receipt_submitted') {
                throw ValidationException::withMessages([
                    'receipt' => 'Penerimaan ini sudah divalidasi atau belum diajukan outlet.',
                ]);
            }

            Outlet::whereKey($receipt->outlet_id)->lockForUpdate()->firstOrFail();
            foreach ($receipt->items as $item) {
                if (! array_key_exists($item->id, $validated['validated'])) {
                    throw ValidationException::withMessages([
                        'validated' => 'Jumlah validasi setiap produk harus diisi.',
                    ]);
                }

                $accepted = (int) $validated['validated'][$item->id];
                if ($accepted > $item->received_quantity) {
                    throw ValidationException::withMessages([
                        'validated' => 'Jumlah tervalidasi tidak boleh melebihi jumlah yang dilaporkan outlet.',
                    ]);
                }

                $item->validated_quantity = $accepted;
                $item->save();

                if ($accepted === 0) {
                    continue;
                }

                $product = Product::whereKey($item->orderItem->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $stock = OutletStock::where('outlet_id', $receipt->outlet_id)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();
                $before = $stock?->quantity ?? 0;

                if ($before + $accepted > 4294967295) {
                    throw ValidationException::withMessages([
                        'validated' => 'Stok outlet melebihi kapasitas untuk '.$product->name.'.',
                    ]);
                }

                $stock ??= new OutletStock([
                    'outlet_id' => $receipt->outlet_id,
                    'product_id' => $product->id,
                ]);
                $stock->quantity = $before + $accepted;
                $stock->save();

                OutletStockTransaction::create([
                    'outlet_id' => $receipt->outlet_id,
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'receipt',
                    'quantity' => $accepted,
                    'stock_before' => $before,
                    'stock_after' => $stock->quantity,
                    'source_type' => 'receipt_item',
                    'source_id' => $item->id,
                    'transaction_date' => now(),
                    'notes' => 'Penerimaan pesanan '.$receipt->order->order_number,
                ]);
            }

            $receipt->update([
                'status' => 'validated',
                'validated_by' => $request->user()->id,
                'validated_at' => now(),
                'notes' => trim(($receipt->notes ? $receipt->notes."\n" : '')
                    .($validated['notes'] ?? '')),
            ]);
            $receipt->order->update(['status' => 'completed']);
        });

        return back()->with('success', 'Penerimaan telah divalidasi dan stok outlet diperbarui.');
    }
}
