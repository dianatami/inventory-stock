@extends('layouts.outlet')
@section('title', 'Stok Akhir Outlet - TPN')
@section('page-title', 'Stok Akhir Outlet')
@section('content')
<div class="outlet-page-head"><div><h1>Stok Opname Harian</h1><p>Masukkan stok fisik akhir. Barang terpakai dihitung dari stok awal + barang diterima − stok akhir fisik.</p></div></div>
<section class="outlet-panel">
    <form class="outlet-form" method="POST" action="{{ route('outlet.stocktake.store') }}">@csrf
        <div class="outlet-field" style="max-width:280px"><label for="stocktake_date">Tanggal opname *</label><input type="date" name="stocktake_date" id="stocktake_date" value="{{ old('stocktake_date', $date) }}" max="{{ today()->toDateString() }}" required></div>
        <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Produk</th><th>Stok outlet tercatat</th><th>Stok akhir fisik</th></tr></thead><tbody>
        @forelse ($products as $product)
            @php($stock = $product->outletStocks->first())
            <tr><td>{{ $product->name }} <span class="outlet-muted">({{ $product->unit }})</span></td><td>{{ $stock?->quantity ?? 0 }}</td><td><input type="number" name="items[{{ $product->id }}]" min="0" max="4294967295" value="{{ old('items.'.$product->id, $stock?->quantity ?? 0) }}" required></td></tr>
        @empty<tr><td colspan="3" class="outlet-empty">Tidak ada produk aktif.</td>@endforelse
        </tbody></table></div>
        <div class="outlet-field"><label for="notes">Catatan selisih / pemeriksaan (opsional)</label><textarea id="notes" name="notes" rows="2">{{ old('notes') }}</textarea></div>
        <button class="btn" type="submit">Simpan Stok Akhir</button>
    </form>
</section>
<section class="outlet-panel"><h2>Riwayat Opname Terakhir</h2>
    <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Tanggal opname</th><th>Jumlah produk</th><th>Laporan pemakaian</th></tr></thead><tbody>
    @forelse ($stocktakes as $stocktake)
        <tr><td>{{ $stocktake->stocktake_date->format('d/m/Y') }}</td><td>{{ $stocktake->items_count }} produk</td><td><a href="{{ route('outlet.usage.index', ['date' => $stocktake->stocktake_date->toDateString()]) }}">Lihat laporan →</a></td></tr>
    @empty<tr><td colspan="3" class="outlet-empty">Belum ada riwayat opname.</td>@endforelse
    </tbody></table></div>
</section>
@endsection
