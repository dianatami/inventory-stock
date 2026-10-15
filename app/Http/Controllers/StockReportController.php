<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'products');

        if (!in_array($tab, ['products', 'in', 'out'])) {
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

        $transactionType = $tab === 'in' ? 'in' : 'out';

        $transactionsQuery = StockTransaction::with([
            'product.category',
            'product.supplier',
            'user',
        ])
            ->where('type', $transactionType)
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

        return view('reports.stock', compact(
            'tab',
            'date',
            'search',
            'products',
            'transactions',
            'dailyIn',
            'dailyOut',
            'dailyInCount',
            'dailyOutCount'
        ));
    }
}