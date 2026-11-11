<?php

namespace App\Http\Controllers;

use App\Models\LogisticsRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LogisticsRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = LogisticsRequest::with(['requester', 'product', 'processor'])->latest();

        if ($request->user()->role === 'Kepala Logistik') {
            $query->where('requested_by', $request->user()->id);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('request_number', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('logistics_requests.index', compact('requests'));
    }

    public function create()
    {
        $products = Product::with('supplier')
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('name')
            ->get();

        return view('logistics_requests.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'reason' => ['required', 'string', 'max:5000'],
        ]);
        $product = Product::whereKey($validated['product_id'])
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->first();

        if (! $product) {
            throw ValidationException::withMessages([
                'product_id' => 'Barang sudah tidak menipis atau tidak tersedia. Perbarui halaman lalu pilih barang dengan stok di bawah batas minimum.',
            ]);
        }

        $logisticsRequest = LogisticsRequest::create([
            'request_number' => 'REQ-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'requested_by' => $request->user()->id,
            'product_id' => $product->id,
            'quantity' => $validated['quantity'],
            'unit' => $product->unit,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('logistics-requests.index')
            ->with('success', 'Permintaan '.$logisticsRequest->request_number.' berhasil diajukan.');
    }

    public function show(Request $request, LogisticsRequest $logisticsRequest)
    {
        if ($request->user()->role === 'Kepala Logistik'
            && $logisticsRequest->requested_by !== $request->user()->id) {
            abort(404);
        }

        $logisticsRequest->load(['requester', 'product.supplier', 'processor']);

        return view('logistics_requests.show', compact('logisticsRequest'));
    }

    public function updateStatus(Request $request, LogisticsRequest $logisticsRequest)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:processing,completed,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (! in_array($logisticsRequest->status, ['pending', 'processing'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Permintaan yang sudah selesai atau ditolak tidak dapat diproses kembali.',
            ]);
        }

        $logisticsRequest->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Status permintaan berhasil diperbarui.');
    }
}
