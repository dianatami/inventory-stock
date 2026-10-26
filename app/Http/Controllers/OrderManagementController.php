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
use App\Models\WorkflowStatusHistory;
use App\Services\LowStockNotificationService;
use App\Services\SystemNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = OutletOrder::with(['outlet', 'user', 'items.product', 'receipt'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('outlet', fn ($outlet) => $outlet->where('name', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('warehouse.orders.index', compact('orders'));
    }

    public function distributions(Request $request)
    {
        $query = OutletOrder::with([
            'outlet',
            'dispatcher',
            'items.product',
            'receipt',
            'statusHistory.user',
        ])
            ->whereIn('status', ['dispatched', 'receipt_submitted', 'completed'])
            ->latest('dispatched_at');
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('outlet', fn ($outlet) => $outlet->where('name', 'like', "%{$search}%"));
            });
        }
        $distributions = $query->paginate(15)->withQueryString();

        return view('warehouse.distributions.index', compact('distributions'));
    }

    public function review(
        Request $request,
        OutletOrder $order,
        SystemNotificationService $systemNotifications
    )
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
                WorkflowStatusHistory::record(
                    $order,
                    'requested',
                    'rejected',
                    $request->user()->id,
                    $validated['decision_notes'] ?? null,
                    (int) $order->items()->sum('requested_quantity')
                );

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
                    $product = Product::whereKey($item->product_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    if (! $product->is_active || $quantity > $product->stock) {
                        throw ValidationException::withMessages([
                            'approved' => 'Stok gudang tidak mencukupi untuk menyetujui '
                                .$product->name.'.',
                        ]);
                    }
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
            WorkflowStatusHistory::record(
                $order,
                'requested',
                'approved',
                $request->user()->id,
                $validated['decision_notes'] ?? null,
                (int) $order->items()->sum('approved_quantity')
            );
        });

        $order->loadMissing('outlet');
        $approved = $validated['decision'] === 'approve';
        $systemNotifications->record(
            'order-review:'.$order->id,
            'purchase_order',
            'Pesanan '.$order->order_number.($approved ? ' disetujui' : ' ditolak'),
            'Pesanan dari '.$order->outlet->name.' '
                .($approved ? 'telah disetujui dan menunggu pengiriman.' : 'ditolak: '.($validated['decision_notes'] ?? 'tanpa catatan alasan.')),
            ['Manager', 'Admin Prolog'],
            'order',
            $order->id,
            $order->order_number
        );

        return back()->with('success', 'Keputusan pesanan berhasil disimpan.');
    }

    public function dispatch(
        Request $request,
        OutletOrder $order,
        LowStockNotificationService $notifications,
        SystemNotificationService $systemNotifications
    ) {
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
                'dispatched_by' => $request->user()->id,
            ]);
            WorkflowStatusHistory::record(
                $order,
                'approved',
                'dispatched',
                $request->user()->id,
                'Pengiriman pesanan dikonfirmasi.',
                (int) $order->items()->sum('dispatched_quantity')
            );
        });

        $notifications->sync();
        $order->loadMissing('outlet');
        $systemNotifications->record(
            'order-dispatch:'.$order->id,
            'distribution',
            'Distribusi ke '.$order->outlet->name.' telah dikirim',
            'Pesanan '.$order->order_number.' telah dikirim; stok gudang sudah dimutasi sesuai jumlah yang disetujui.',
            ['Manager', 'Admin Prolog'],
            'order',
            $order->id,
            $order->order_number
        );

        return back()->with('success', 'Barang berhasil dikirim dan stok gudang telah diperbarui.');
    }

    public function receipts(Request $request)
    {
        $query = OutletReceipt::with([
            'outlet',
            'order.items.product',
            'items.orderItem.product',
            'items.orderItem',
        ])->latest('submitted_at');
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('order', fn ($order) => $order->where('order_number', 'like', "%{$search}%"));
        }
        $receipts = $query->paginate(15)->withQueryString();

        return view('warehouse.receipts.index', compact('receipts'));
    }

    public function validateReceipt(
        Request $request,
        OutletReceipt $receipt,
        SystemNotificationService $systemNotifications
    )
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
                $usableReceived = $item->received_quantity - $item->damaged_quantity;
                if ($accepted > $usableReceived) {
                    throw ValidationException::withMessages([
                        'validated' => 'Jumlah layak tervalidasi tidak boleh melebihi jumlah yang diterima dikurangi barang rusak.',
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
            WorkflowStatusHistory::record(
                $receipt,
                'submitted',
                'validated',
                $request->user()->id,
                $validated['notes'] ?? null,
                (int) $receipt->items()->sum('validated_quantity')
            );
            WorkflowStatusHistory::record(
                $receipt->order,
                'receipt_submitted',
                'completed',
                $request->user()->id,
                'Penerimaan tervalidasi oleh petugas gudang.',
                (int) $receipt->items()->sum('validated_quantity')
            );
            $receipt->order->update(['status' => 'completed']);
        });

        $receipt->loadMissing('outlet', 'order');
        $systemNotifications->record(
            'receipt-validated:'.$receipt->id,
            'distribution',
            'Penerimaan '.$receipt->order->order_number.' telah divalidasi',
            'Penerimaan barang dari '.$receipt->outlet->name.' sudah diperiksa dan stok outlet diperbarui sesuai jumlah layak.',
            ['Manager', 'Admin Prolog'],
            'order',
            $receipt->order_id,
            $receipt->order->order_number
        );

        return back()->with('success', 'Penerimaan telah divalidasi dan stok outlet diperbarui.');
    }
}
