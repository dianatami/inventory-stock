@extends('layouts.app')

@section('title', 'Notifikasi Stok Menipis')
@section('page-title', 'Notifikasi Stok Menipis')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>Notifikasi Stok Menipis</h1>
            <p>Produk aktif dengan stok sama dengan atau di bawah batas minimum.</p>
        </div>
        @if (auth()->user()->role === 'Manager')
            <a href="{{ route('products.index', ['status' => 'active']) }}" class="btn btn-primary">Kelola Produk</a>
        @endif
    </div>
    <div class="card">
        <form action="{{ route('notifications.low-stock') }}" method="GET">
            <div class="filter-row">
                <div class="form-group">
                    <label for="search">Cari produk</label>
                    <input class="form-control" type="search" name="search" id="search"
                           value="{{ request('search') }}" placeholder="Kode atau nama produk">
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Cari</button>
                <a class="btn btn-secondary" href="{{ route('notifications.low-stock') }}">Reset</a>
            </div>
        </form>
    </div>
    <div class="card">
        <div class="page-header">
            <div><h2>Produk yang Perlu Diperiksa</h2></div>
            <span class="badge">{{ $products->total() }} produk</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>No.</th><th>Produk</th><th>Kategori</th><th>Stok saat ini</th><th>Stok minimum</th><th>Kekurangan</th></tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>{{ $products->firstItem() + $loop->index }}</td>
                            <td><strong>{{ $product->name }}</strong><br><small>{{ $product->code }}</small></td>
                            <td>{{ $product->category?->name ?? '-' }}</td>
                            <td><span class="status-badge status-low">{{ number_format($product->stock, 0, ',', '.') }} {{ $product->unit }}</span></td>
                            <td>{{ number_format($product->minimum_stock, 0, ',', '.') }} {{ $product->unit }}</td>
                            <td>{{ number_format(max(0, $product->minimum_stock - $product->stock), 0, ',', '.') }} {{ $product->unit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">Tidak ada produk dengan stok menipis.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $products->links() }}</div>
    </div>
</div>
@endsection
