@extends('layouts.app')

@section('title', 'Laporan Stok Harian')
@section('page-title', 'Laporan Stok Harian')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>Laporan Stok Harian</h1>
            <p>Ringkasan seluruh pergerakan stok pada tanggal yang dipilih.</p>
        </div>
        <a href="{{ route('reports.stock') }}" class="btn btn-secondary">Laporan Stok Keseluruhan</a>
    </div>

    <div class="card">
        <form action="{{ route('reports.daily') }}" method="GET">
            <div class="report-filter-row">
                <div class="form-group">
                    <label for="date">Tanggal laporan</label>
                    <input class="form-control" type="date" id="date" name="date"
                           value="{{ $date }}" required>
                </div>
                <div class="form-group">
                    <label for="search">Cari produk (opsional)</label>
                    <input class="form-control" type="search" id="search" name="search"
                           value="{{ $search }}" placeholder="Kode atau nama produk">
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Tampilkan Laporan</button>
                <a class="btn btn-secondary" href="{{ route('reports.daily') }}">Hari Ini</a>
            </div>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">↓</div>
            <div>
                <p>Barang Masuk</p>
                <h2>{{ number_format((int) $dailyStats->quantity_in, 0, ',', '.') }}</h2>
                <small>{{ number_format((int) $dailyStats->count_in, 0, ',', '.') }} transaksi</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">↑</div>
            <div>
                <p>Barang Keluar</p>
                <h2>{{ number_format((int) $dailyStats->quantity_out, 0, ',', '.') }}</h2>
                <small>{{ number_format((int) $dailyStats->count_out, 0, ',', '.') }} transaksi</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">±</div>
            <div>
                <p>Penyesuaian</p>
                <h2>{{ number_format((int) $dailyStats->quantity_adjustment, 0, ',', '.') }}</h2>
                <small>{{ number_format((int) $dailyStats->count_adjustment, 0, ',', '.') }} transaksi</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">◷</div>
            <div>
                <p>Tanggal Laporan</p>
                <h2 class="daily-report-date">{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d F Y') }}</h2>
                <small>{{ $transactions->total() }} transaksi tercatat</small>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="page-header">
            <div>
                <h2>Rincian Pergerakan Stok</h2>
                <p>{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d F Y') }}</p>
            </div>
            <span class="badge">{{ $transactions->total() }} transaksi</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Waktu</th>
                        <th>Jenis</th>
                        <th>Produk</th>
                        <th>Jumlah</th>
                        <th>Stok Sebelum</th>
                        <th>Stok Sesudah</th>
                        <th>Tujuan / Referensi</th>
                        <th>Petugas</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        @php
                            $isIncoming = $transaction->type === 'in'
                                || ($transaction->type === 'adjustment'
                                    && $transaction->stock_after > $transaction->stock_before);
                            $typeLabel = $transaction->type === 'adjustment'
                                ? 'Penyesuaian'
                                : ($transaction->type === 'in' ? 'Masuk' : 'Keluar');
                        @endphp
                        <tr>
                            <td>{{ $transactions->firstItem() + $loop->index }}</td>
                            <td>{{ $transaction->transaction_date->format('H:i') }}</td>
                            <td>
                                <span class="{{ $isIncoming ? 'quantity-in' : 'quantity-out' }}">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $transaction->product?->name ?? 'Produk tidak tersedia' }}</strong><br>
                                <small>{{ $transaction->product?->code }}</small>
                            </td>
                            <td>
                                {{ $isIncoming ? '+' : '−' }}{{ number_format($transaction->quantity, 0, ',', '.') }}
                                {{ $transaction->product?->unit }}
                            </td>
                            <td>{{ number_format($transaction->stock_before, 0, ',', '.') }}</td>
                            <td>{{ number_format($transaction->stock_after, 0, ',', '.') }}</td>
                            <td>
                                {{ $transaction->destination ?: ($transaction->reference_number ?: '-') }}
                            </td>
                            <td>{{ $transaction->user?->name ?? '-' }}</td>
                            <td>{{ $transaction->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty-state">
                                Tidak ada transaksi stok pada tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
