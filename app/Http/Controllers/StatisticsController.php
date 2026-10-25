<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;

class StatisticsController extends Controller
{
    public function index()
    {
        $summary = [
            'products' => Product::count(),
            'stock' => Product::sum('stock'),
            'categories' => Category::count(),
            'suppliers' => Supplier::count(),
            'users' => User::where('is_active', true)->count(),
            'low_stock' => Product::where('is_active', true)
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->count(),
        ];

        $monthly = collect(range(11, 0))->map(function (int $monthsAgo) {
            $month = now()->startOfMonth()->subMonths($monthsAgo);
            $rows = StockTransaction::whereBetween('transaction_date', [
                $month->copy(),
                $month->copy()->endOfMonth(),
            ])->selectRaw(
                "SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as quantity_in, "
                ."SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as quantity_out, "
                .'COUNT(*) as transactions'
            )->first();

            return [
                'label' => $month->locale('id')->translatedFormat('M Y'),
                'incoming' => (int) ($rows->quantity_in ?? 0),
                'outgoing' => (int) ($rows->quantity_out ?? 0),
                'transactions' => (int) ($rows->transactions ?? 0),
            ];
        });

        $categoryStats = Category::withCount('products')
            ->withSum('products as total_stock', 'stock')
            ->orderByDesc('products_count')
            ->limit(10)
            ->get();

        $topMovedProducts = Product::with('category')
            ->withCount('stockTransactions')
            ->orderByDesc('stock_transactions_count')
            ->limit(10)
            ->get();

        $chartMax = max(1, $monthly->max(
            fn (array $item) => max($item['incoming'], $item['outgoing'])
        ));

        return view('statistics.index', compact(
            'summary',
            'monthly',
            'categoryStats',
            'topMovedProducts',
            'chartMax'
        ));
    }
}
