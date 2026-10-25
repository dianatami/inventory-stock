@extends('layouts.outlet')
@section('title', 'Dashboard Outlet - TPN')
@section('page-title', 'Dashboard Outlet')
@section('content')
<div class="outlet-page-head"><div><h1>Halo, {{ auth()->user()->name }}</h1><p>Ringkasan persediaan dan aktivitas {{ $outlet->name }}.</p></div></div>
<div class="outlet-grid">
    <div class="outlet-stat"><span>Jenis produk aktif</span><strong>{{ $stocks->count() }}</strong></div>
    <div class="outlet-stat"><span>Total unit stok</span><strong>{{ number_format($stocks->sum('quantity'), 0, ',', '.') }}</strong></div>
    <div class="outlet-stat"><span>Stok menipis</span><strong>{{ $lowStocks->count() }}</strong></div>
    <div class="outlet-stat"><span>Pesanan terbaru</span><strong>{{ $orders->count() }}</strong></div>
</div>
@if ($lowStocks->isNotEmpty())
    <div class="outlet-alert warning"><strong>Perhatian:</strong> {{ $lowStocks->count() }} produk berada pada atau di bawah batas minimum stok.</div>
@endif
<div class="outlet-actions" style="margin-bottom:18px">
    <a class="btn" href="{{ route('outlet.orders.create') }}">+ Pesan Barang</a>
    <a class="btn" style="background:#f4c542;color:#35120f" href="{{ route('outlet.stocktake') }}">Input Stok Opname</a>
    <a class="btn" style="background:#fff;color:#8f1d14;border:1px solid #b42318" href="{{ route('outlet.returns.create') }}">Ajukan Retur</a>
</div>
<section class="outlet-panel">
    <h2>Stok Outlet Menipis</h2>
    <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Produk</th><th>Kategori</th><th>Stok saat ini</th><th>Batas minimum</th><th>Status</th></tr></thead><tbody>
    @forelse ($lowStocks as $stock)
        <tr><td>{{ $stock->product->name }}</td><td>{{ $stock->product->category?->name ?? '-' }}</td><td>{{ $stock->quantity }} {{ $stock->product->unit }}</td><td>{{ $stock->product->minimum_stock }} {{ $stock->product->unit }}</td><td><span class="outlet-badge">Perlu diperiksa</span></td></tr>
    @empty<tr><td colspan="5" class="outlet-empty">Tidak ada stok menipis.</td></tr>@endforelse
    </tbody></table></div>
</section>
<section class="outlet-panel">
    <h2>Pesanan Terbaru</h2>
    <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Item</th><th>Status</th></tr></thead><tbody>
    @forelse ($orders as $order)
        <tr><td>{{ $order->order_number }}</td><td>{{ $order->created_at->format('d/m/Y H:i') }}</td><td>{{ $order->items->count() }} produk</td><td><span class="outlet-badge">{{ str_replace('_', ' ', ucfirst($order->status)) }}</span></td></tr>
    @empty<tr><td colspan="4" class="outlet-empty">Belum ada pesanan barang.</td></tr>@endforelse
    </tbody></table></div>
</section>
<section class="outlet-panel"><h2>Stok Opname Terakhir</h2>
    @if ($latestStocktake)
        <p>{{ $latestStocktake->stocktake_date->format('d/m/Y') }} · {{ $latestStocktake->items->count() }} produk dicatat</p>
        <a href="{{ route('outlet.usage.index', ['date' => $latestStocktake->stocktake_date->toDateString()]) }}">Lihat laporan barang terpakai →</a>
    @else<p class="outlet-muted">Belum ada stok opname tersimpan.</p>@endif
</section>
@endsection
