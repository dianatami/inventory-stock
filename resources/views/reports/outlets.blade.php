@extends('layouts.app')
@section('title', 'Laporan Rekap Outlet - MieTime Logistik')
@section('page-title', 'Laporan Rekap Outlet')
@section('content')
<div class="container">
    <div class="outlet-report-hero">
        <div class="outlet-report-heading">
            <span class="outlet-report-icon"><svg class="interface-icon" aria-hidden="true"><use href="#icon-store"/></svg></span>
            <div>
                <p class="outlet-report-eyebrow">RINGKASAN PERSEDIAAN</p>
                <h1>Laporan Rekap Outlet</h1>
                <p>Pantau ketersediaan barang dan stok menipis di seluruh cabang.</p>
            </div>
        </div>
    </div>

    <div class="outlet-report-stats">
        <article class="outlet-report-stat">
            <span class="outlet-report-stat-icon"><svg class="interface-icon" aria-hidden="true"><use href="#icon-store"/></svg></span>
            <div><p>Total Outlet</p><strong>{{ number_format($summary['outlets'], 0, ',', '.') }}</strong><small>Seluruh cabang</small></div>
        </article>
        <article class="outlet-report-stat">
            <span class="outlet-report-stat-icon"><svg class="interface-icon" aria-hidden="true"><use href="#icon-package"/></svg></span>
            <div><p>Total Stok Outlet</p><strong>{{ number_format($summary['stock_units'], 0, ',', '.') }}</strong><small>Unit di seluruh cabang</small></div>
        </article>
        <article class="outlet-report-stat is-warning">
            <span class="outlet-report-stat-icon"><svg class="interface-icon" aria-hidden="true"><use href="#icon-alert"/></svg></span>
            <div><p>Catatan Stok Menipis</p><strong>{{ number_format($summary['low_stock_products'], 0, ',', '.') }}</strong><small>Di seluruh cabang</small></div>
        </article>
    </div>

    <section class="card outlet-report-content">
        <div class="outlet-report-section-heading">
            <div>
                <h2>Rincian Stok per Outlet</h2>
                <p>Data berdasarkan stok outlet yang tersimpan saat ini.</p>
            </div>
            <span class="outlet-report-count">{{ number_format($outlets->total(), 0, ',', '.') }} outlet</span>
        </div>

        <form action="{{ route('reports.outlets') }}" method="GET" class="outlet-report-filters">
            <div class="form-group">
                <label for="outlet-search">Cabang</label>
                <input class="form-control" type="search" id="outlet-search" name="search"
                       value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama atau kode outlet">
            </div>
            <div class="form-group">
                <label for="stock-status">Status persediaan</label>
                <select class="form-control" id="stock-status" name="status">
                    <option value="all" @selected($status === 'all')>Semua status</option>
                    <option value="low" @selected($status === 'low')>Ada stok menipis</option>
                    <option value="safe" @selected($status === 'safe')>Stok aman</option>
                </select>
            </div>
            <div class="outlet-report-filter-actions">
                <button class="btn btn-primary" type="submit">Tampilkan</button>
                <a class="btn btn-secondary" href="{{ route('reports.outlets') }}">Reset</a>
            </div>
        </form>

        <div class="outlet-report-list">
        @forelse ($outlets as $outlet)
            @php
                $totalQuantity = $outlet->stocks->sum('quantity');
                $lowStockCount = $outlet->stocks->filter(fn ($stock) => $stock->product && $stock->quantity <= $stock->product->minimum_stock)->count();
            @endphp
            <details class="outlet-report-card">
                <summary class="outlet-report-card-summary">
                    <span class="outlet-report-branch-mark">{{ mb_strtoupper(mb_substr($outlet->name, 0, 1)) }}</span>
                    <span class="outlet-report-branch-name"><strong>{{ $outlet->branch_display_name }}</strong><small>{{ $outlet->code }}</small></span>
                    <span class="outlet-report-metric"><strong>{{ number_format($outlet->stocks->count(), 0, ',', '.') }}</strong><small>Jenis barang</small></span>
                    <span class="outlet-report-metric"><strong>{{ number_format($totalQuantity, 0, ',', '.') }}</strong><small>Total unit</small></span>
                    <span class="report-status {{ $lowStockCount > 0 ? 'low' : ($outlet->stocks->isEmpty() ? 'inactive' : 'safe') }}">
                        {{ $lowStockCount > 0 ? $lowStockCount.' stok menipis' : ($outlet->stocks->isEmpty() ? 'Belum ada stok' : 'Stok aman') }}
                    </span>
                    <span class="outlet-report-chevron" aria-hidden="true">⌄</span>
                </summary>
                <div class="table-responsive outlet-report-table-wrap">
                    <table class="table">
                        <thead><tr><th>Kode barang</th><th>Nama barang</th><th>Stok outlet</th><th>Batas minimum</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($outlet->stocks as $stock)
                                @if ($stock->product)
                                    <tr>
                                        <td><span class="outlet-report-code">{{ $stock->product->code }}</span></td>
                                        <td><strong>{{ $stock->product->name }}</strong></td>
                                        <td>{{ number_format($stock->quantity, 0, ',', '.') }} {{ $stock->product->unit }}</td>
                                        <td>{{ number_format($stock->product->minimum_stock, 0, ',', '.') }} {{ $stock->product->unit }}</td>
                                        <td><span class="report-status {{ $stock->quantity <= $stock->product->minimum_stock ? 'low' : 'safe' }}">{{ $stock->quantity <= $stock->product->minimum_stock ? 'Menipis' : 'Aman' }}</span></td>
                                    </tr>
                                @endif
                            @empty
                                <tr><td colspan="5" class="empty-state">Belum ada catatan stok outlet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </details>
        @empty
            <div class="outlet-report-empty">
                <span class="outlet-report-empty-icon"><svg class="interface-icon" aria-hidden="true"><use href="#icon-search"/></svg></span>
                <strong>{{ ($filters['search'] ?? '') || $status !== 'all' ? 'Outlet tidak ditemukan' : 'Belum ada data outlet' }}</strong>
                <p>{{ ($filters['search'] ?? '') || $status !== 'all' ? 'Coba ubah kata kunci atau filter status persediaan.' : 'Data outlet akan tampil di sini setelah tersedia.' }}</p>
            </div>
        @endforelse
        </div>
        @if ($outlets->hasPages())
            <div class="outlet-report-pagination">{{ $outlets->links() }}</div>
        @endif
    </div>
</div>
@endsection
