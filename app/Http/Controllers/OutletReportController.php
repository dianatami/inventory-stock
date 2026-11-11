<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\OutletStock;
use Illuminate\Http\Request;

class OutletReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:all,low,safe'],
        ]);
        $status = $filters['status'] ?? 'all';

        $query = Outlet::with(['stocks.product'])
            ->orderBy('name')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            });

        if ($status === 'low') {
            $query->whereHas('stocks', function ($query) {
                $query->whereHas('product', function ($query) {
                    $query->whereColumn('outlet_stocks.quantity', '<=', 'products.minimum_stock');
                });
            });
        } elseif ($status === 'safe') {
            $query->whereHas('stocks')
                ->whereDoesntHave('stocks', function ($query) {
                    $query->whereHas('product', function ($query) {
                        $query->whereColumn('outlet_stocks.quantity', '<=', 'products.minimum_stock');
                    });
                });
        }

        $outlets = $query->paginate(15)->withQueryString();
        $summary = [
            'outlets' => Outlet::count(),
            'stock_units' => (int) OutletStock::sum('quantity'),
            'low_stock_products' => OutletStock::query()
                ->join('products', 'products.id', '=', 'outlet_stocks.product_id')
                ->whereColumn('outlet_stocks.quantity', '<=', 'products.minimum_stock')
                ->count(),
        ];

        return view('reports.outlets', compact('outlets', 'summary', 'status', 'filters'));
    }
}
