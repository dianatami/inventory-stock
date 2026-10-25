@extends('layouts.outlet')
@section('title', 'Laporan Data Stok - TPN')
@section('page-title', 'Laporan Data Stok')
@section('content')
<div class="outlet-page-head"><div><h1>Persediaan Outlet</h1><p>Stok gudang dan stok outlet dicatat secara terpisah.</p></div></div>
<section class="outlet-panel">
    <form method="GET" action="{{ route('outlet.stocks.index') }}" class="outlet-form-grid" style="margin-bottom:16px">
        <div class="outlet-field"><label for="search">Cari produk</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Nama atau kode produk"></div>
        <div class="outlet-field" style="align-self:end"><button class="btn" type="submit">Cari Produk</button></div>
    </form>
    <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Kode</th><th>Produk</th><th>Kategori</th><th>Stok outlet</th><th>Batas minimum</th><th>Status</th></tr></thead><tbody>
    @forelse ($products as $product)
        @php($stock = $product->outletStocks->first())
        <tr><td>{{ $product->code }}</td><td><strong>{{ $product->name }}</strong></td><td>{{ $product->category?->name ?? '-' }}</td><td>{{ $stock?->quantity ?? 0 }} {{ $product->unit }}</td><td>{{ $product->minimum_stock }} {{ $product->unit }}</td><td>@if (($stock?->quantity ?? 0) <= $product->minimum_stock)<span class="outlet-badge">Menipis</span>@else<span class="outlet-muted">Aman</span>@endif</td></tr>
    @empty<tr><td colspan="6" class="outlet-empty">Produk tidak ditemukan.</td></tr>@endforelse
    </tbody></table></div>
    <div style="margin-top:16px">{{ $products->links() }}</div>
</section>
@endsection
