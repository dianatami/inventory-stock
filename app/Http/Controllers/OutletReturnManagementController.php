<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\OutletReturn;
use App\Models\OutletStock;
use App\Models\OutletStockTransaction;
use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutletReturnManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = OutletReturn::with(['outlet', 'order', 'items.product'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $returns = $query->paginate(15)->withQueryString();

        return view('warehouse.returns.index', compact('returns'));
    }

    public function review(Request $request, OutletReturn $return)
    {
        abort_unless(in_array($request->user()->role, ['Manager', 'Kepala Logistik'], true), 403);
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'decision_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($validated, $request, $return) {
            $locked = OutletReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'requested') {
                throw ValidationException::withMessages([
                    'decision' => 'Pengajuan retur sudah diproses.',
                ]);
            }

            $locked->update([
                'status' => $validated['decision'] === 'approve' ? 'approved' : 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'decision_notes' => $validated['decision_notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Keputusan retur berhasil disimpan.');
    }

    public function verify(Request $request, OutletReturn $return)
    {
        abort_unless(in_array($request->user()->role, ['Manager', 'Admin Prolog'], true), 403);
        $validated = $request->validate([
            'verified' => ['required', 'array'],
            'verified.*' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ]);

        DB::transaction(function () use ($validated, $request, $return) {
            $return = OutletReturn::with('items.product')
                ->whereKey($return->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($return->status !== 'approved') {
                throw ValidationException::withMessages([
                    'verified' => 'Barang retur hanya dapat diverifikasi setelah disetujui.',
                ]);
            }

            Outlet::whereKey($return->outlet_id)->lockForUpdate()->firstOrFail();
            foreach ($return->items as $item) {
                if (! array_key_exists($item->id, $validated['verified'])) {
                    throw ValidationException::withMessages([
                        'verified' => 'Jumlah verifikasi setiap produk harus diisi.',
                    ]);
                }

                $quantity = (int) $validated['verified'][$item->id];
                if ($quantity > $item->requested_quantity) {
                    throw ValidationException::withMessages([
                        'verified' => 'Jumlah diterima gudang tidak boleh melebihi pengajuan retur.',
                    ]);
                }
                if ($quantity === 0) {
                    $item->verified_quantity = 0;
                    $item->save();

                    continue;
                }

                $stock = OutletStock::where('outlet_id', $return->outlet_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();
                if (! $stock || $stock->quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'verified' => 'Stok outlet tidak cukup untuk memproses retur '
                            .$item->product->name.'.',
                    ]);
                }

                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                if ($product->stock + $quantity > 4294967295) {
                    throw ValidationException::withMessages([
                        'verified' => 'Stok gudang melebihi kapasitas untuk '.$product->name.'.',
                    ]);
                }

                $outletBefore = $stock->quantity;
                $stock->quantity -= $quantity;
                $stock->save();
                $item->verified_quantity = $quantity;
                $item->save();

                OutletStockTransaction::create([
                    'outlet_id' => $return->outlet_id,
                    'product_id' => $item->product_id,
                    'user_id' => $request->user()->id,
                    'type' => 'return',
                    'quantity' => $quantity,
                    'stock_before' => $outletBefore,
                    'stock_after' => $stock->quantity,
                    'source_type' => 'return_item',
                    'source_id' => $item->id,
                    'transaction_date' => now(),
                    'notes' => 'Retur '.$return->return_number,
                ]);

                $warehouseBefore = $product->stock;
                $product->stock += $quantity;
                $product->save();
                StockTransaction::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'in',
                    'quantity' => $quantity,
                    'stock_before' => $warehouseBefore,
                    'stock_after' => $product->stock,
                    'reference_number' => $return->return_number,
                    'destination' => 'Gudang - Retur outlet '.$return->outlet->name,
                    'transaction_date' => now(),
                    'notes' => 'Retur diverifikasi dan diterima gudang',
                ]);
            }

            $return->update([
                'status' => 'verified',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);
        });

        return back()->with('success', 'Barang retur telah diverifikasi. Stok outlet dan gudang diperbarui.');
    }
}
