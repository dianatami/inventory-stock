@extends('layouts.app')

@section('title', 'Mutasi Stok')
@section('page-title', 'Mutasi Stok')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>Mutasi Stok</h1>
            <p>Catatan perpindahan stok dari gudang ke outlet, tersinkron dengan distribusi barang.</p>
        </div>
        <a href="{{ route('warehouse.receipts.index') }}" class="btn btn-primary">Buka Distribusi Barang</a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon">⇄</div><div><p>Total Mutasi</p><h2>{{ number_format($totalMutations, 0, ',', '.') }}</h2></div></div>
        <div class="stat-card"><div class="stat-icon">▦</div><div><p>Total Unit Dipindahkan</p><h2>{{ number_format($totalQuantity, 0, ',', '.') }}</h2></div></div>
    </div>

    <div class="card">
        <form action="{{ route('stock-mutations.index') }}" method="GET">
            <div class="filter-row">
                <div class="form-group"><label for="search">Cari produk atau lokasi</label><input class="form-control" type="search" name="search" id="search" value="{{ request('search') }}" placeholder="Kode, nama, lokasi asal/tujuan"></div>
                <div class="form-group"><label for="start_date">Dari tanggal</label><input class="form-control" type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"></div>
                <div class="form-group"><label for="end_date">Sampai tanggal</label><input class="form-control" type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Cari</button><a class="btn btn-secondary" href="{{ route('stock-mutations.index') }}">Reset</a></div>
        </form>
    </div>

    <div class="card">
        <div class="page-header"><div><h2>Riwayat Mutasi Antar Lokasi</h2></div><span class="badge">{{ $mutations->total() }} mutasi</span></div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>No.</th><th>Tanggal</th><th>Produk</th><th>Dari lokasi</th><th>Ke lokasi</th><th>Jumlah</th><th>Status</th><th>Petugas</th><th>Catatan</th></tr></thead>
                <tbody>
                    @forelse ($mutations as $mutation)
                        <tr>
                            <td>{{ $mutations->firstItem() + $loop->index }}</td>
                            <td>{{ $mutation->mutation_date->format('d M Y H:i') }}</td>
                            <td><strong>{{ $mutation->product?->name ?? 'Produk tidak tersedia' }}</strong><br><small>{{ $mutation->product?->code }}</small></td>
                            <td>{{ $mutation->from_location }}</td>
                            <td>{{ $mutation->to_location }}</td>
                            <td>{{ number_format($mutation->quantity, 0, ',', '.') }} {{ $mutation->product?->unit }}</td>
                            <td><span class="status-badge status-safe">{{ ucfirst($mutation->status) }}</span></td>
                            <td>{{ $mutation->user?->name ?? '-' }}</td>
                            <td>{{ $mutation->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty-state">Belum ada mutasi stok antar lokasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $mutations->links() }}</div>
    </div>
</div>
@endsection
