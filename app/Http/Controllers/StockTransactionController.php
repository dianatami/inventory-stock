<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransaction;
use App\Services\LowStockNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = StockTransaction::with(['product', 'user']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate(
                'transaction_date',
                '>=',
                $request->start_date
            );
        }

        if ($request->filled('end_date')) {
            $query->whereDate(
                'transaction_date',
                '<=',
                $request->end_date
            );
        }

        $transactions = $query
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view(
            'stock_transactions.index',
            compact('transactions')
        );
    }

    public function create()
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'stock_transactions.create',
            compact('products')
        );
    }

    public function store(Request $request, LowStockNotificationService $notifications)
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
            'type' => [
                'required',
                'in:in,out',
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:4294967295',
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'transaction_date' => [
                'required',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        $transaction = DB::transaction(function () use (
            $validated,
            $request
        ) {
            $product = Product::whereKey($validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'Produk ini tidak aktif.',
                ]);
            }

            $stockBefore = $product->stock;
            $quantity = (int) $validated['quantity'];

            if (
                $validated['type'] === 'out' &&
                $quantity > $stockBefore
            ) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok tidak mencukupi. Stok tersedia: '
                        .$stockBefore.' '.$product->unit.'.',
                ]);
            }

            if ($validated['type'] === 'in') {
                $stockAfter = $stockBefore + $quantity;
            } else {
                $stockAfter = $stockBefore - $quantity;
            }

            if ($stockAfter > 4294967295) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah stok melebihi batas maksimum.',
                ]);
            }

            $product->stock = $stockAfter;
            $product->save();

            return StockTransaction::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_number' => $validated['reference_number'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $notifications->sync();

        return redirect()
            ->route(
                $validated['type'] === 'in'
                    ? 'stock-in.index'
                    : 'stock-out.index'
            )
            ->with('success', 'Transaksi stok berhasil disimpan. Nomor transaksi: '
                .$transaction->id);
    }

    public function stockIn(Request $request)
    {
        return $this->transactionPage($request, 'in');
    }

    public function stockOut(Request $request)
    {
        return $this->transactionPage($request, 'out');
    }

    private function transactionPage(Request $request, string $type)
    {
        $query = \App\Models\StockTransaction::with(['product', 'user'])
            ->where('type', $type);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        $statsQuery = \App\Models\StockTransaction::where('type', $type);

        $totalTransactions = (clone $statsQuery)->count();

        $todayTransactions = (clone $statsQuery)
            ->whereDate('transaction_date', today())
            ->count();

        $monthTransactions = (clone $statsQuery)
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->count();

        $totalQuantity = (clone $statsQuery)->sum('quantity');

        $transactions = $query
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $pageType = $type === 'in' ? 'Stok Masuk' : 'Stok Keluar';

        return view('stock_transactions.index', compact(
            'transactions',
            'totalTransactions',
            'todayTransactions',
            'monthTransactions',
            'totalQuantity',
            'pageType',
            'type'
        ));
    }

    public function createStockIn()
    {
        $products = \App\Models\Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        $type = 'in';

        return view('stock_transactions.create', compact('products', 'type'));
    }

    public function createStockOut()
    {
        $products = \App\Models\Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        $type = 'out';

        return view('stock_transactions.create', compact('products', 'type'));
    }

    public function storeStockIn(Request $request, LowStockNotificationService $notifications)
    {
        $request->merge(['type' => 'in']);

        return $this->store($request, $notifications);
    }

    public function storeStockOut(Request $request, LowStockNotificationService $notifications)
    {
        $request->merge(['type' => 'out']);

        return $this->store($request, $notifications);
    }
}
