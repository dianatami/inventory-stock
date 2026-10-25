@extends('layouts.app')

@section('title', 'Penyesuaian Stok')
@section('page-title', 'Penyesuaian Stok')

@section('content')
<div class="container">
    <div class="page-header">
        <div><h1>Penyesuaian Stok</h1><p>Riwayat koreksi stok berdasarkan hasil opname fisik.</p></div>
        <a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary">+ Input Opname</a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card">
        <form action="{{ route('stock-adjustments.index') }}" method="GET">
            <div class="filter-row">
                <div class="form-group"><label for="search">Cari produk</label><input class="form-control" type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Kode atau nama produk"></div>
                <div class="form-group"><label for="start_date">Dari tanggal</label><input class="form-control" type="date" id="start_date" name="start_date" value="{{ request('start_date') }}"></div>
                <div class="form-group"><label for="end_date">Sampai tanggal</label><input class="form-control" type="date" id="end_date" name="end_date" value="{{ request('end_date') }}"></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Cari</button><a class="btn btn-secondary" href="{{ route('stock-adjustments.index') }}">Reset</a></div>
        </form>
    </div>
    <div class="card">
        <div class="page-header"><div><h2>Riwayat Opname</h2></div><span class="badge">{{ $adjustments->total() }} penyesuaian</span></div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>No.</th><th>Tanggal</th><th>Produk</th><th>Stok sebelum</th><th>Selisih</th><th>Stok fisik</th><th>Alasan</th><th>Petugas</th></tr></thead>
                <tbody>
                    @forelse ($adjustments as $item)
                        <tr>
                            <td>{{ $adjustments->firstItem() + $loop->index }}</td>
                            <td>{{ $item->transaction_date->format('d M Y H:i') }}</td>
                            <td><strong>{{ $item->product?->name ?? 'Produk tidak tersedia' }}</strong><br><small>{{ $item->product?->code }}</small></td>
                            <td>{{ number_format($item->stock_before, 0, ',', '.') }}</td>
                            <td><span class="{{ $item->stock_after > $item->stock_before ? 'quantity-in' : 'quantity-out' }}">{{ $item->stock_after > $item->stock_before ? '+' : '−' }}{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product?->unit }}</span></td>
                            <td>{{ number_format($item->stock_after, 0, ',', '.') }} {{ $item->product?->unit }}</td>
                            <td>{{ $item->notes }}</td>
                            <td>{{ $item->user?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">Belum ada penyesuaian stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $adjustments->links() }}</div>
    </div>
</div>
@endsection
