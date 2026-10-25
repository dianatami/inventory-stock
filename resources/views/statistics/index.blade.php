@extends('layouts.app')

@section('title', 'Statistik Data')
@section('page-title', 'Statistik Data')

@section('content')
<div class="container">
    <div class="page-header">
        <div><h1>Statistik Data</h1><p>Ringkasan persediaan, master data, dan pergerakan stok.</p></div>
        <a href="{{ route('reports.stock') }}" class="btn btn-secondary">Laporan stok keseluruhan</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon">▣</div><div><p>Total Produk</p><h2>{{ number_format($summary['products'], 0, ',', '.') }}</h2></div></div>
        <div class="stat-card"><div class="stat-icon">▦</div><div><p>Total Persediaan</p><h2>{{ number_format($summary['stock'], 0, ',', '.') }}</h2></div></div>
        <div class="stat-card"><div class="stat-icon">!</div><div><p>Stok Menipis</p><h2>{{ number_format($summary['low_stock'], 0, ',', '.') }}</h2></div></div>
        <div class="stat-card"><div class="stat-icon">▤</div><div><p>Kategori</p><h2>{{ number_format($summary['categories'], 0, ',', '.') }}</h2></div></div>
        <div class="stat-card"><div class="stat-icon">♧</div><div><p>Supplier</p><h2>{{ number_format($summary['suppliers'], 0, ',', '.') }}</h2></div></div>
        <div class="stat-card"><div class="stat-icon">♙</div><div><p>Pengguna Aktif</p><h2>{{ number_format($summary['users'], 0, ',', '.') }}</h2></div></div>
    </div>

    <section class="section-panel dashboard-chart">
        <div class="section-heading">
            <div><h3>Pergerakan Stok 12 Bulan</h3><p>Total unit barang masuk dan keluar per bulan.</p></div>
            <div class="chart-legend"><span><i class="legend-in"></i> Masuk</span><span><i class="legend-out"></i> Keluar</span></div>
        </div>
        <div class="year-chart" role="img" aria-label="Pergerakan stok masuk dan keluar 12 bulan terakhir">
            @foreach ($monthly as $month)
                <div class="chart-month">
                    <div class="chart-bars">
                        <div class="chart-bar chart-bar-in" style="height: {{ $month['incoming'] ? max(4, $month['incoming'] / $chartMax * 100) : 0 }}%" title="Masuk: {{ $month['incoming'] }}"></div>
                        <div class="chart-bar chart-bar-out" style="height: {{ $month['outgoing'] ? max(4, $month['outgoing'] / $chartMax * 100) : 0 }}%" title="Keluar: {{ $month['outgoing'] }}"></div>
                    </div>
                    <span>{{ $month['label'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="table-responsive statistics-month-table">
            <table class="table">
                <thead><tr><th>Bulan</th><th>Jumlah transaksi</th><th>Unit masuk</th><th>Unit keluar</th><th>Selisih bersih</th></tr></thead>
                <tbody>
                    @foreach ($monthly->reverse() as $month)
                        <tr><td>{{ $month['label'] }}</td><td>{{ number_format($month['transactions'], 0, ',', '.') }}</td><td>{{ number_format($month['incoming'], 0, ',', '.') }}</td><td>{{ number_format($month['outgoing'], 0, ',', '.') }}</td><td>{{ number_format($month['incoming'] - $month['outgoing'], 0, ',', '.') }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="dashboard-detail-grid">
        <section class="section-panel">
            <div class="section-heading"><div><h3>Ringkasan per Kategori</h3><p>Jumlah produk dan stok tiap kategori.</p></div></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Kategori</th><th>Jumlah produk</th><th>Total stok</th></tr></thead>
                    <tbody>
                        @forelse ($categoryStats as $category)
                            <tr><td><strong>{{ $category->name }}</strong></td><td>{{ number_format($category->products_count, 0, ',', '.') }}</td><td>{{ number_format($category->total_stock ?? 0, 0, ',', '.') }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="empty-state">Belum ada data kategori.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="section-panel">
            <div class="section-heading"><div><h3>Produk Paling Sering Bergerak</h3><p>Berdasarkan jumlah catatan transaksi.</p></div></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Produk</th><th>Kategori</th><th>Transaksi</th></tr></thead>
                    <tbody>
                        @forelse ($topMovedProducts as $product)
                            <tr><td><strong>{{ $product->name }}</strong><br><small>{{ $product->code }}</small></td><td>{{ $product->category?->name ?? '-' }}</td><td>{{ number_format($product->stock_transactions_count, 0, ',', '.') }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="empty-state">Belum ada data produk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
