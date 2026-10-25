<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OutletController extends Controller
{
    public function index()
    {
        $outlets = Outlet::with('user')->withCount('stocks')->orderBy('name')->paginate(15);

        return view('outlets.index', compact('outlets'));
    }

    public function create()
    {
        return view('outlets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:outlets,code'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        Outlet::create($validated + ['is_active' => true]);

        return redirect()->route('outlets.index')
            ->with('success', 'Outlet berhasil ditambahkan. Akun outlet dapat dibuat dari Manajemen Pengguna.');
    }

    public function edit(Outlet $outlet)
    {
        return view('outlets.edit', compact('outlet'));
    }

    public function update(Request $request, Outlet $outlet)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('outlets', 'code')->ignore($outlet->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
        ]);

        if (! $validated['is_active'] && $outlet->user()->exists()) {
            $outlet->user()->update(['is_active' => false]);
        }

        $outlet->update($validated);

        return redirect()->route('outlets.index')->with('success', 'Data outlet berhasil diperbarui.');
    }

    public function destroy(Outlet $outlet)
    {
        if ($outlet->user()->exists() || $outlet->orders()->exists()
            || $outlet->stocks()->exists() || $outlet->stocktakes()->exists()) {
            return back()->with('error', 'Outlet yang memiliki riwayat transaksi atau stok tidak dapat dihapus. Nonaktifkan outlet sebagai gantinya.');
        }

        User::where('outlet_id', $outlet->id)->update(['outlet_id' => null]);
        $outlet->delete();

        return redirect()->route('outlets.index')->with('success', 'Outlet berhasil dihapus.');
    }
}
