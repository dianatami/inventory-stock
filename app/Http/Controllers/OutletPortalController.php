<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\OutletOrder;
use App\Models\OutletReceipt;
use App\Models\OutletReceiptItem;
use App\Models\OutletReturn;
use App\Models\OutletReturnItem;
use App\Models\OutletStock;
use App\Models\OutletStocktake;
use App\Models\OutletStocktakeItem;
use App\Models\OutletStockTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OutletPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $outlet = $this->outlet($request);
        $stocks = OutletStock::with('product.category')
            ->where('outlet_id', $outlet->id)
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->get();
        $lowStocks = $stocks->filter(
            fn (OutletStock $stock) => $stock->quantity <= $stock->product->minimum_stock
        );
        $orders = OutletOrder::with('items.product')
            ->where('outlet_id', $outlet->id)
            ->latest()
            ->limit(5)
            ->get();
        $latestStocktake = OutletStocktake::with('items.product')
            ->where('outlet_id', $outlet->id)
            ->latest('stocktake_date')
            ->first();

        return view('outlet.dashboard', compact(
            'outlet',
            'stocks',
            'lowStocks',
            'orders',
            'latestStocktake'
        ));
    }

    public function stocks(Request $request)
    {
        $outlet = $this->outlet($request);
        $search = $request->input('search');
        $query = Product::with(['category', 'outletStocks' => function ($stockQuery) use ($outlet) {
            $stockQuery->where('outlet_id', $outlet->id);
        }])->where('is_active', true);

        if ($search) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('outlet.stocks', compact('outlet', 'products'));
    }

    public function orders(Request $request)
    {
        $outlet = $this->outlet($request);
        $orders = OutletOrder::with(['items.product', 'receipt'])
            ->where('outlet_id', $outlet->id)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('outlet.orders.index', compact('outlet', 'orders'));
    }

    public function createOrder(Request $request)
    {
        $outlet = $this->outlet($request);
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('outlet.orders.create', compact('outlet', 'products'));
    }

    public function storeOrder(Request $request)
    {
        $outlet = $this->outlet($request);
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $order = DB::transaction(function () use ($validated, $request, $outlet) {
            $order = OutletOrder::create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'outlet_id' => $outlet->id,
                'user_id' => $request->user()->id,
                'status' => 'requested',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                if ((int) $item['quantity'] === 0) {
                    continue;
                }

                $product = Product::whereKey($item['product_id'])
                    ->where('is_active', true)
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Salah satu produk tidak aktif atau tidak tersedia.',
                    ]);
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'requested_quantity' => $item['quantity'],
                ]);
            }

            if (! $order->items()->exists()) {
                throw ValidationException::withMessages([
                    'items' => 'Masukkan jumlah minimal satu produk untuk membuat pesanan.',
                ]);
            }

            return $order;
        });

        return redirect()->route('outlet.orders.index')
            ->with('success', 'Pesanan '.$order->order_number.' berhasil diajukan.');
    }

    public function submitReceipt(Request $request, OutletOrder $order)
    {
        $outlet = $this->outlet($request);
        $validated = $request->validate([
            'received' => ['required', 'array', 'min:1'],
            'received.*' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($validated, $request, $order, $outlet) {
            $lockedOrder = OutletOrder::whereKey($order->id)
                ->where('outlet_id', $outlet->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== 'dispatched' || $lockedOrder->receipt()->exists()) {
                throw ValidationException::withMessages([
                    'received' => 'Pesanan belum dikirim atau penerimaan sudah pernah diajukan.',
                ]);
            }

            $items = $lockedOrder->items()->get()->keyBy('id');
            $receiptLines = [];
            $hasReceived = false;

            foreach ($validated['received'] as $itemId => $quantity) {
                $orderItem = $items->get((int) $itemId);
                if (! $orderItem || $quantity > $orderItem->dispatched_quantity) {
                    throw ValidationException::withMessages([
                        'received' => 'Jumlah diterima tidak boleh melebihi jumlah yang dikirim.',
                    ]);
                }

                $hasReceived = $hasReceived || (int) $quantity > 0;
                $receiptLines[] = [
                    'outlet_order_item_id' => $orderItem->id,
                    'received_quantity' => (int) $quantity,
                ];
            }

            if (! $hasReceived) {
                throw ValidationException::withMessages([
                    'received' => 'Masukkan jumlah yang benar-benar diterima.',
                ]);
            }

            $receipt = OutletReceipt::create([
                'outlet_order_id' => $lockedOrder->id,
                'outlet_id' => $outlet->id,
                'submitted_by' => $request->user()->id,
                'status' => 'submitted',
                'notes' => $validated['notes'] ?? null,
                'submitted_at' => now(),
            ]);
            $receipt->items()->createMany($receiptLines);
            $lockedOrder->update(['status' => 'receipt_submitted']);
        });

        return redirect()->route('outlet.orders.index')
            ->with('success', 'Konfirmasi penerimaan dikirim untuk divalidasi gudang.');
    }

    public function returns(Request $request)
    {
        $outlet = $this->outlet($request);
        $returns = OutletReturn::with(['items.product', 'order'])
            ->where('outlet_id', $outlet->id)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('outlet.returns.index', compact('outlet', 'returns'));
    }

    public function createReturn(Request $request)
    {
        $outlet = $this->outlet($request);
        $receipts = OutletReceipt::with(['items.orderItem.product', 'order'])
            ->where('outlet_id', $outlet->id)
            ->where('status', 'validated')
            ->whereHas('items', fn ($query) => $query->where('validated_quantity', '>', 0))
            ->latest('validated_at')
            ->get();

        return view('outlet.returns.create', compact('outlet', 'receipts'));
    }

    public function storeReturn(Request $request)
    {
        $outlet = $this->outlet($request);
        $validated = $request->validate([
            'receipt_id' => ['required', 'integer', 'exists:outlet_receipts,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        $returnRequest = DB::transaction(function () use ($validated, $request, $outlet) {
            Outlet::whereKey($outlet->id)->lockForUpdate()->firstOrFail();
            $receipt = OutletReceipt::with('items.orderItem')
                ->whereKey($validated['receipt_id'])
                ->where('outlet_id', $outlet->id)
                ->where('status', 'validated')
                ->lockForUpdate()
                ->firstOrFail();

            $items = [];
            foreach ($validated['items'] as $receiptItemId => $quantity) {
                $quantity = (int) ($quantity ?? 0);
                if ($quantity === 0) {
                    continue;
                }

                $receiptItem = $receipt->items->firstWhere('id', (int) $receiptItemId);
                if (! $receiptItem) {
                    throw ValidationException::withMessages([
                        'items' => 'Produk retur tidak sesuai dengan penerimaan terpilih.',
                    ]);
                }

                $alreadyRequested = OutletReturnItem::where('product_id', $receiptItem->orderItem->product_id)
                    ->whereHas('returnRequest', function ($query) use ($receipt) {
                        $query->where('outlet_receipt_id', $receipt->id)
                            ->whereIn('status', ['requested', 'approved', 'verified']);
                    })
                    ->sum('requested_quantity');
                $available = OutletStock::where('outlet_id', $outlet->id)
                    ->where('product_id', $receiptItem->orderItem->product_id)
                    ->lockForUpdate()
                    ->value('quantity') ?? 0;

                if ($quantity + $alreadyRequested > $receiptItem->validated_quantity
                    || $quantity > $available) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah retur melebihi barang diterima atau stok outlet yang tersedia.',
                    ]);
                }

                $items[] = [
                    'product_id' => $receiptItem->orderItem->product_id,
                    'requested_quantity' => $quantity,
                ];
            }

            if ($items === []) {
                throw ValidationException::withMessages([
                    'items' => 'Isi jumlah produk yang akan diretur.',
                ]);
            }

            $returnRequest = OutletReturn::create([
                'return_number' => 'RET-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'outlet_order_id' => $receipt->outlet_order_id,
                'outlet_receipt_id' => $receipt->id,
                'outlet_id' => $outlet->id,
                'requested_by' => $request->user()->id,
                'status' => 'requested',
                'reason' => $validated['reason'],
            ]);
            $returnRequest->items()->createMany($items);

            return $returnRequest;
        });

        return redirect()->route('outlet.returns.index')
            ->with('success', 'Pengajuan retur '.$returnRequest->return_number.' berhasil dikirim.');
    }

    public function stocktake(Request $request)
    {
        $outlet = $this->outlet($request);
        $date = $request->validate(['date' => ['nullable', 'date']])['date'] ?? today()->toDateString();
        $products = Product::where('is_active', true)->with(['outletStocks' => function ($query) use ($outlet) {
            $query->where('outlet_id', $outlet->id);
        }])->orderBy('name')->get();
        $stocktakes = OutletStocktake::withCount('items')
            ->where('outlet_id', $outlet->id)
            ->latest('stocktake_date')
            ->limit(10)
            ->get();

        return view('outlet.stocktake', compact('outlet', 'products', 'date', 'stocktakes'));
    }

    public function storeStocktake(Request $request)
    {
        $outlet = $this->outlet($request);
        $validated = $request->validate([
            'stocktake_date' => ['required', 'date', 'before_or_equal:today'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($validated, $request, $outlet) {
            Outlet::whereKey($outlet->id)->lockForUpdate()->firstOrFail();
            $date = $validated['stocktake_date'];

            if (OutletStocktake::where('outlet_id', $outlet->id)
                ->whereDate('stocktake_date', $date)->exists()) {
                throw ValidationException::withMessages([
                    'stocktake_date' => 'Stok opname untuk tanggal ini sudah pernah disimpan.',
                ]);
            }

            $previous = OutletStocktake::where('outlet_id', $outlet->id)
                ->whereDate('stocktake_date', '<', $date)
                ->latest('stocktake_date')
                ->first();
            $latest = OutletStocktake::where('outlet_id', $outlet->id)
                ->latest('stocktake_date')
                ->first();

            if ($latest && $date < $latest->stocktake_date->toDateString()) {
                throw ValidationException::withMessages([
                    'stocktake_date' => 'Tanggal opname tidak boleh lebih awal dari opname terakhir.',
                ]);
            }

            $productIds = array_map('intval', array_keys($validated['items']));
            $products = Product::whereIn('id', $productIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');
            if ($products->count() !== count(array_unique($productIds))) {
                throw ValidationException::withMessages([
                    'items' => 'Daftar produk berisi produk yang tidak aktif atau tidak tersedia.',
                ]);
            }

            $stocktake = OutletStocktake::create([
                'outlet_id' => $outlet->id,
                'user_id' => $request->user()->id,
                'stocktake_date' => $date,
                'notes' => $validated['notes'] ?? null,
            ]);
            $previousItems = $previous
                ? $previous->items()->get()->keyBy('product_id')
                : collect();

            foreach ($products as $productId => $product) {
                $previousItem = $previousItems->get($productId);
                $opening = $previousItem?->closing_stock ?? 0;

                $receiptQuery = OutletReceiptItem::whereHas('receipt', function ($query) use ($outlet, $previous, $date) {
                    $query->where('outlet_id', $outlet->id)
                        ->where('status', 'validated')
                        ->whereDate('validated_at', '<=', $date);
                    if ($previous) {
                        $query->whereDate('validated_at', '>', $previous->stocktake_date);
                    }
                })->whereHas('orderItem', fn ($query) => $query->where('product_id', $product->id));
                $received = (int) $receiptQuery->sum('validated_quantity');
                $closing = (int) $validated['items'][$productId];
                $availableBeforeUse = $opening + $received;
                $used = $availableBeforeUse - $closing;
                $discrepancy = max(0, -$used);

                $line = $stocktake->items()->create([
                    'product_id' => $product->id,
                    'opening_stock' => $opening,
                    'received_quantity' => $received,
                    'closing_stock' => $closing,
                    'used_quantity' => $used,
                    'discrepancy' => $discrepancy,
                ]);

                $stock = OutletStock::where('outlet_id', $outlet->id)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();
                $stockBefore = $stock?->quantity ?? 0;
                $stock ??= new OutletStock([
                    'outlet_id' => $outlet->id,
                    'product_id' => $product->id,
                ]);
                $stock->quantity = $closing;
                $stock->save();

                if ($stockBefore !== $closing) {
                    OutletStockTransaction::create([
                        'outlet_id' => $outlet->id,
                        'product_id' => $product->id,
                        'user_id' => $request->user()->id,
                        'type' => 'stocktake',
                        'quantity' => abs($closing - $stockBefore),
                        'stock_before' => $stockBefore,
                        'stock_after' => $closing,
                        'source_type' => 'stocktake_item',
                        'source_id' => $line->id,
                        'transaction_date' => $date.' 23:59:59',
                        'notes' => $validated['notes'] ?? 'Penyesuaian hasil stok opname',
                    ]);
                }
            }
        });

        return redirect()->route('outlet.stocktake', ['date' => $validated['stocktake_date']])
            ->with('success', 'Stok akhir outlet berhasil dicatat.');
    }

    public function usage(Request $request)
    {
        $outlet = $this->outlet($request);
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);
        $date = $validated['date'] ?? today()->toDateString();
        $query = OutletStocktakeItem::with(['product', 'stocktake'])
            ->whereHas('stocktake', function ($builder) use ($outlet, $date) {
                $builder->where('outlet_id', $outlet->id)
                    ->whereDate('stocktake_date', $date);
            });

        if (! empty($validated['product_id'])) {
            $query->where('product_id', $validated['product_id']);
        }

        $usageItems = $query->orderBy('product_id')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('outlet.usage', compact('outlet', 'date', 'usageItems', 'products'));
    }

    private function outlet(Request $request)
    {
        $outlet = $request->user()->outlet;
        abort_unless($outlet && $outlet->is_active, 403, 'Akun ini belum terhubung ke outlet aktif.');

        return $outlet;
    }
}
