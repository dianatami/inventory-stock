<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LowStockNotification;
use App\Models\OutletOrder;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\LowStockNotificationService;

class DashboardController extends Controller
{
    public function __invoke(LowStockNotificationService $notifications)
    {
        $notifications->sync();
        $metrics = [
            'products' => Product::count(),
            'stock' => Product::sum('stock'),
            'low_stock' => Product::where('is_active', true)
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->count(),
            'today_in' => StockTransaction::where('type', 'in')
                ->whereDate('transaction_date', today())
                ->count(),
            'today_out' => StockTransaction::where('type', 'out')
                ->whereDate('transaction_date', today())
                ->count(),
            'suppliers' => Supplier::count(),
            'users' => User::count(),
            'categories' => Category::count(),
        ];

        $monthlyTransactions = collect(range(5, 0))
            ->map(function (int $monthsAgo) {
                $month = now()->startOfMonth()->subMonths($monthsAgo);
                $counts = StockTransaction::whereBetween('transaction_date', [
                    $month->copy(),
                    $month->copy()->endOfMonth(),
                ])->selectRaw(
                    'SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as incoming, '
                    .'SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as outgoing',
                    ['in', 'out']
                )->first();

                return [
                    'label' => $month->locale('id')->translatedFormat('M'),
                    'incoming' => (int) ($counts->incoming ?? 0),
                    'outgoing' => (int) ($counts->outgoing ?? 0),
                ];
            });

        $chartMax = max(1, $monthlyTransactions->max(
            fn (array $month) => max($month['incoming'], $month['outgoing'])
        ));

        $recentStockIn = StockTransaction::with(['product', 'user'])
            ->where('type', 'in')
            ->latest('transaction_date')
            ->latest('id')
            ->limit(6)
            ->get();

        $lowStockProducts = Product::with('category')
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock')
            ->limit(8)
            ->get();
        $activeNotifications = LowStockNotification::with('product')
            ->whereNull('resolved_at')
            ->latest('notified_at')
            ->limit(8)
            ->get();
        $newOrders = in_array(auth()->user()->role, ['Manager', 'Admin Prolog'], true)
            ? OutletOrder::with('outlet', 'items.product')
                ->where('status', 'requested')
                ->latest()
                ->limit(5)
                ->get()
            : collect();
        $newOrdersCount = in_array(auth()->user()->role, ['Manager', 'Admin Prolog'], true)
            ? OutletOrder::where('status', 'requested')->count()
            : 0;

        return view('dashboard', compact(
            'metrics',
            'monthlyTransactions',
            'chartMax',
            'recentStockIn',
            'lowStockProducts',
            'activeNotifications',
            'newOrders',
            'newOrdersCount'
        ));
    }
}
