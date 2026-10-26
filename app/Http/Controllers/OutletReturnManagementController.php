<?php

namespace App\Http\Controllers;

use App\Models\OutletReturn;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\WorkflowStatusHistory;
use App\Services\LowStockNotificationService;
use App\Services\SystemNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutletReturnManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = OutletReturn::with([
            'outlet',
            'order',
            'dispatcher',
            'items.product',
            'statusHistory.user',
        ])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('return_number', 'like', "%{$search}%")
                    ->orWhereHas('outlet', fn ($outlet) => $outlet->where('name', 'like', "%{$search}%"));
            });
        }

        $returns = $query->paginate(15)->withQueryString();

        return view('warehouse.returns.index', compact('returns'));
    }

    public function review(
        Request $request,
        OutletReturn $return,
        SystemNotificationService $systemNotifications
    )
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
            WorkflowStatusHistory::record(
                $locked,
                'requested',
                $locked->status,
                $request->user()->id,
                $validated['decision_notes'] ?? null,
                (int) $locked->items()->sum('requested_quantity')
            );
        });

        $return->loadMissing('outlet');
        $approved = $validated['decision'] === 'approve';
        $systemNotifications->record(
            'return-review:'.$return->id,
            'return',
            'Retur '.$return->return_number.($approved ? ' disetujui' : ' ditolak'),
            'Pengajuan retur dari '.$return->outlet->name.' '
                .($approved ? 'disetujui dan dapat dikirim kembali ke gudang.' : 'ditolak.'),
            ['Manager', 'Admin Prolog'],
            'return',
            $return->id,
            $return->return_number
        );

        return back()->with('success', 'Keputusan retur berhasil disimpan.');
    }

    public function verify(
        Request $request,
        OutletReturn $return,
        LowStockNotificationService $notifications,
        SystemNotificationService $systemNotifications
    ) {
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
            if ($return->status !== 'dispatched') {
                throw ValidationException::withMessages([
                    'verified' => 'Barang retur hanya dapat diverifikasi setelah dikirim outlet.',
                ]);
            }

            foreach ($return->items as $item) {
                if (! array_key_exists($item->id, $validated['verified'])) {
                    throw ValidationException::withMessages([
                        'verified' => 'Jumlah verifikasi setiap produk harus diisi.',
                    ]);
                }

                $quantity = (int) $validated['verified'][$item->id];
                if ($quantity > $item->shipped_quantity) {
                    throw ValidationException::withMessages([
                        'verified' => 'Jumlah layak yang diterima gudang tidak boleh melebihi jumlah retur yang dikirim.',
                    ]);
                }
                if ($quantity === 0) {
                    $item->verified_quantity = 0;
                    $item->save();

                    continue;
                }

                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                if ($product->stock + $quantity > 4294967295) {
                    throw ValidationException::withMessages([
                        'verified' => 'Stok gudang melebihi kapasitas untuk '.$product->name.'.',
                    ]);
                }

                $item->verified_quantity = $quantity;
                $item->save();

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
            WorkflowStatusHistory::record(
                $return,
                'dispatched',
                'verified',
                $request->user()->id,
                'Gudang mencatat barang layak diterima.',
                (int) $return->items()->sum('verified_quantity')
            );
        });

        $notifications->sync();
        $return->loadMissing('outlet');
        $systemNotifications->record(
            'return-verified:'.$return->id,
            'return',
            'Retur '.$return->return_number.' telah diverifikasi',
            'Barang retur dari '.$return->outlet->name.' telah diperiksa dan stok gudang diperbarui.',
            ['Manager', 'Admin Prolog'],
            'return',
            $return->id,
            $return->return_number
        );

        return back()->with('success', 'Retur diverifikasi. Stok gudang bertambah sesuai barang yang layak jual.');
    }
}
