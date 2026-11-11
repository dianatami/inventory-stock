<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\OutletOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OutletOrderRecapController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);
        if (! empty($validated['start_date'])
            && ! empty($validated['end_date'])
            && $validated['end_date'] < $validated['start_date']) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            ]);
        }

        $query = OutletOrder::with(['outlet', 'items.product'])->latest();

        if (! empty($validated['outlet_id'])) {
            $query->where('outlet_id', $validated['outlet_id']);
        }
        if (! empty($validated['start_date'])) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }
        if (! empty($validated['end_date'])) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }

        $orders = $query->paginate(15)->withQueryString();
        $outlets = Outlet::orderBy('name')->get(['id', 'name']);

        return view('orders.recap.index', compact('orders', 'outlets'));
    }

    public function show(OutletOrder $order)
    {
        $order->load(['outlet', 'user', 'items.product', 'statusHistory.user']);

        return view('orders.recap.show', compact('order'));
    }
}
