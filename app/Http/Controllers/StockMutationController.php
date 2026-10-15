<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockMutationController extends Controller
{
    
public function index(Request $request)
{
    $query = \App\Models\StockTransaction::with([
        'product.supplier',
        'user'
    ])->whereIn('type', ['in', 'out']);

    // Filter jenis transaksi
    if ($request->filled('type') &&
        in_array($request->type, ['in', 'out'])) {
        $query->where('type', $request->type);
    }

    // Pencarian produk
    if ($request->filled('search')) {
        $search = $request->search;

        $query->whereHas('product', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }

    // Filter tanggal
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

    // Statistik seluruh transaksi masuk dan keluar
    $statsQuery = \App\Models\StockTransaction::whereIn(
        'type',
        ['in', 'out']
    );

    $totalTransactions = (clone $statsQuery)->count();

    $totalIn = (clone $statsQuery)
        ->where('type', 'in')
        ->sum('quantity');

    $totalOut = (clone $statsQuery)
        ->where('type', 'out')
        ->sum('quantity');

    $netFlow = $totalIn - $totalOut;

    $transactions = $query
        ->latest('transaction_date')
        ->latest('id')
        ->paginate(10)
        ->withQueryString();

    return view('stock_mutations.index', compact(
        'transactions',
        'totalTransactions',
        'totalIn',
        'totalOut',
        'netFlow'
    ));
}


    public function create()
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stock_mutations.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:4294967295',
            ],
            'from_location' => [
                'required',
                'string',
                'max:255',
                'different:to_location',
            ],
            'to_location' => [
                'required',
                'string',
                'max:255',
            ],
            'mutation_date' => [
                'required',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        $product = Product::whereKey($validated['product_id'])
            ->where('is_active', true)
            ->first();

        if (!$product) {
            throw ValidationException::withMessages([
                'product_id' => 'Produk tidak ditemukan atau sudah tidak aktif.',
            ]);
        }

        $mutation = StockMutation::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'quantity' => $validated['quantity'],
            'from_location' => $validated['from_location'],
            'to_location' => $validated['to_location'],
            'status' => 'completed',
            'mutation_date' => $validated['mutation_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('stock-mutations.index')
            ->with(
                'success',
                'Data mutasi stok berhasil disimpan. Nomor mutasi: '
                . $mutation->id
            );
    }
}