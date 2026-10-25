<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    public function daily(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $date = $validated['date'] ?? today()->toDateString();
        $search = $validated['search'] ?? null;

        $query = StockTransaction::with([
            'product.category',
            'product.supplier',
            'user',
        ])->whereDate('transaction_date', $date);

        if ($search) {
            $query->whereHas('product', function ($productQuery) use ($search) {
                $productQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('transaction_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $dailyStats = StockTransaction::whereDate('transaction_date', $date)
            ->selectRaw(
                "SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as quantity_in, "
                ."SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as quantity_out, "
                ."SUM(CASE WHEN type = 'adjustment' THEN quantity ELSE 0 END) as quantity_adjustment, "
                ."SUM(CASE WHEN type = 'in' THEN 1 ELSE 0 END) as count_in, "
                ."SUM(CASE WHEN type = 'out' THEN 1 ELSE 0 END) as count_out, "
                ."SUM(CASE WHEN type = 'adjustment' THEN 1 ELSE 0 END) as count_adjustment"
            )
            ->first();

        return view('reports.daily', compact(
            'date',
            'search',
            'transactions',
            'dailyStats'
        ));
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'products');

        if (! in_array($tab, ['products', 'in', 'out', 'adjustment'], true)) {
            $tab = 'products';
        }

        $search = $request->get('search');

        // Tanggal laporan harian.
        // Jika tanggal tidak dipilih, gunakan hari ini.
        $date = $request->get('date', now()->toDateString());

        $productsQuery = Product::with('category')
            ->orderBy('name');

        if ($search) {
            $productsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $products = $productsQuery->get();

        $transactionsQuery = StockTransaction::with([
            'product.category',
            'product.supplier',
            'user',
        ])
            ->where('type', $tab)
            ->whereDate('transaction_date', $date);

        if ($search) {
            $transactionsQuery->whereHas('product', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $transactions = $transactionsQuery
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        // Ringkasan laporan transaksi pada tanggal terpilih.
        $dailyIn = StockTransaction::where('type', 'in')
            ->whereDate('transaction_date', $date)
            ->sum('quantity');

        $dailyOut = StockTransaction::where('type', 'out')
            ->whereDate('transaction_date', $date)
            ->sum('quantity');

        $dailyInCount = StockTransaction::where('type', 'in')
            ->whereDate('transaction_date', $date)
            ->count();

        $dailyOutCount = StockTransaction::where('type', 'out')
            ->whereDate('transaction_date', $date)
            ->count();

        $dailyAdjustment = StockTransaction::where('type', 'adjustment')
            ->whereDate('transaction_date', $date)
            ->sum('quantity');

        $dailyAdjustmentCount = StockTransaction::where('type', 'adjustment')
            ->whereDate('transaction_date', $date)
            ->count();

        return view('reports.stock', compact(
            'tab',
            'date',
            'search',
            'products',
            'transactions',
            'dailyIn',
            'dailyOut',
            'dailyInCount',
            'dailyOutCount',
            'dailyAdjustment',
            'dailyAdjustmentCount'
        ));
    }
}
