<?php

namespace App\Http\Controllers;

use App\Models\StockMutation;
use Illuminate\Http\Request;

class StockMutationController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMutation::with(['product', 'user']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('from_location', 'like', "%{$search}%")
                    ->orWhere('to_location', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('mutation_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('mutation_date', '<=', $request->input('end_date'));
        }

        $totalMutations = StockMutation::count();
        $totalQuantity = StockMutation::sum('quantity');
        $mutations = $query->latest('mutation_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('stock_mutations.index', compact(
            'mutations',
            'totalMutations',
            'totalQuantity'
        ));
    }
}
