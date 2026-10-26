<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransaction;
use App\Services\LowStockNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StockTransaction::with(['product', 'user'])
            ->where('type', 'adjustment');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('product', function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->input('end_date'));
        }

        $adjustments = $query->latest('transaction_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('stock_adjustments.index', compact('adjustments'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('stock_adjustments.create', compact('products'));
    }

    public function store(Request $request, LowStockNotificationService $notifications)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'actual_stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        $transaction = DB::transaction(function () use ($validated, $request) {
            $product = Product::whereKey($validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'Produk ini tidak aktif.',
                ]);
            }

            $stockBefore = $product->stock;
            $stockAfter = (int) $validated['actual_stock'];
            if ($stockAfter === $stockBefore) {
                throw ValidationException::withMessages([
                    'actual_stock' => 'Stok fisik sama dengan stok sistem. Tidak ada penyesuaian yang perlu dicatat.',
                ]);
            }

            $product->stock = $stockAfter;
            $product->save();

            return StockTransaction::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'type' => 'adjustment',
                'quantity' => abs($stockAfter - $stockBefore),
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'],
            ]);
        });

        $notifications->sync();

        return redirect()->route('stock-adjustments.index')
            ->with('success', 'Penyesuaian stok berhasil dicatat. Nomor transaksi: '.$transaction->id);
    }
}
