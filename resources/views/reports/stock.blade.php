
@extends('layouts.app')

@section('title', 'Laporan Stok - OMPROF')
@section('page-title', 'Laporan Stok')

@section('content')
<div class="container">

    {{-- JUDUL --}}
    <div class="page-header">
        <div>
            <h1>LAPORAN INVENTORI</h1>
            <p>Laporan kondisi stok dan pergerakan produk.</p>
        </div>
    </div>

    {{-- TAB LAPORAN --}}
    <div class="report-tabs">
        <a href="{{ route('reports.stock', ['tab' => 'products']) }}"
           class="report-tab {{ $tab === 'products' ? 'active' : '' }}">
            ▣ Stok Produk
        </a>

        <a href="{{ route('reports.stock', ['tab' => 'in', 'date' => $date]) }}"
           class="report-tab {{ $tab === 'in' ? 'active' : '' }}">
            ↓ Stok Masuk
        </a>

        <a href="{{ route('reports.stock', ['tab' => 'out', 'date' => $date]) }}"
           class="report-tab {{ $tab === 'out' ? 'active' : '' }}">
            ↑ Stok Keluar
        </a>

        <a href="{{ route('reports.stock', ['tab' => 'adjustment', 'date' => $date]) }}"
           class="report-tab {{ $tab === 'adjustment' ? 'active' : '' }}">
            ± Penyesuaian
        </a>
    </div>

    {{-- RINGKASAN LAPORAN HARIAN --}}
    @if ($tab !== 'products')
        <div class="stats-grid">

            <div class="stat-card">
                <div class="stat-icon">▤</div>
                <div>
                    <p>Total Transaksi {{ $tab === 'in' ? 'Masuk' : ($tab === 'out' ? 'Keluar' : 'Penyesuaian') }}</p>
                    <h2>
                        {{ number_format($tab === 'in' ? $dailyInCount : ($tab === 'out' ? $dailyOutCount : $dailyAdjustmentCount), 0, ',', '.') }}
                    </h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">{{ $tab === 'in' ? '↓' : '↑' }}</div>
                <div>
                    <p>Total Kuantitas {{ $tab === 'in' ? 'Masuk' : ($tab === 'out' ? 'Keluar' : 'Disesuaikan') }}</p>
                    <h2>
                        {{ number_format($tab === 'in' ? $dailyIn : ($tab === 'out' ? $dailyOut : $dailyAdjustment), 0, ',', '.') }}
                    </h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⇄</div>
                <div>
                    <p>{{ $tab === 'adjustment' ? 'Stok Masuk − Keluar' : 'Net Flow Hari Ini' }}</p>
                    <h2>{{ number_format($dailyIn - $dailyOut, 0, ',', '.') }}</h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">▦</div>
                <div>
                    <p>Tanggal Laporan</p>
                    <h2 style="font-size: 17px;">
                        {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
                    </h2>
                </div>
            </div>

        </div>
    @endif

    {{-- FILTER --}}
    <div class="card">
        <form action="{{ route('reports.stock') }}" method="GET">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="report-filter-row">

                <div class="form-group">
                    <label for="search">Cari Produk</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Nama atau kode produk..."
                    >
                </div>

                @if ($tab !== 'products')
                    <div class="form-group">
                        <label for="date">Tanggal Laporan Harian</label>
                        <input
                            type="date"
                            id="date"
                            name="date"
                            value="{{ $date }}"
                            required
                        >
                    </div>
                @endif

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Terapkan Filter
                </button>

                <a href="{{ route('reports.stock', ['tab' => $tab]) }}"
                   class="btn btn-secondary">
                    Reset
                </a>

                <button type="button"
                        class="btn btn-secondary"
                        onclick="window.location.reload()">
                    ↻ Refresh
                </button>
            </div>
        </form>
    </div>

    {{-- TABEL STOK PRODUK --}}
    @if ($tab === 'products')
        <div class="card">
            <div class="page-header">
                <div>
                    <h2>Stok Produk</h2>
                    <p>Menampilkan kondisi stok produk saat ini.</p>
                </div>

                <span class="badge">
                    {{ $products->count() }} data ditemukan
                </span>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kode</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Min. Stok</th>
                            <th>Unit</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                </td>

                                <td>{{ $product->code }}</td>

                                <td>
                                    {{ $product->category->name ?? '-' }}
                                </td>

                                <td>
                                    {{ number_format($product->stock, 0, ',', '.') }}
                                </td>

                                <td>
                                    {{ number_format($product->minimum_stock, 0, ',', '.') }}
                                </td>

                                <td>{{ $product->unit }}</td>

                                <td>
                                    @if (!$product->is_active)
                                        <span class="report-status inactive">
                                            Nonaktif
                                        </span>
                                    @elseif ($product->stock <= $product->minimum_stock)
                                        <span class="report-status low">
                                            Stok Rendah
                                        </span>
                                    @else
                                        <span class="report-status safe">
                                            Aman
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    Tidak ada data produk.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else

        {{-- TABEL STOK MASUK / KELUAR --}}
        <div class="card">
            <div class="page-header">
                <div>
                    <h2>
                        Laporan Stok {{ $tab === 'in' ? 'Masuk' : ($tab === 'out' ? 'Keluar' : 'Penyesuaian') }}
                    </h2>
                    <p>
                        Tanggal:
                        {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
                    </p>
                </div>

                <span class="badge">
                    {{ $transactions->count() }} transaksi ditemukan
                </span>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal & Waktu</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Kuantitas</th>
                            <th>Stok Sebelum</th>
                            <th>Stok Sesudah</th>
                            <th>Pengguna</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    {{ $transaction->transaction_date
                                        ? $transaction->transaction_date->format('d M Y H:i')
                                        : '-' }}
                                </td>

                                <td>
                                    <strong>{{ $transaction->product->name ?? '-' }}</strong>
                                    <br>
                                    <small>{{ $transaction->product->code ?? '' }}</small>
                                </td>

                                <td>
                                    {{ $transaction->product->category->name ?? '-' }}
                                </td>

                                <td>
                                    <span class="{{ $tab === 'in' || ($tab === 'adjustment' && $transaction->stock_after > $transaction->stock_before) ? 'quantity-in' : 'quantity-out' }}">
                                        {{ $tab === 'adjustment' ? ($transaction->stock_after > $transaction->stock_before ? '+' : '−') : ($tab === 'in' ? '+' : '−') }}{{ number_format($transaction->quantity, 0, ',', '.') }}
                                        {{ $transaction->product->unit ?? '' }}
                                    </span>
                                </td>

                                <td>
                                    {{ number_format($transaction->stock_before, 0, ',', '.') }}
                                </td>

                                <td>
                                    {{ number_format($transaction->stock_after, 0, ',', '.') }}
                                </td>

                                <td>{{ $transaction->user->name ?? '-' }}</td>

                                <td>
                                    {{ $transaction->notes ?: '-' }}
                                    @if ($transaction->reference_number)
                                        <br>
                                        <small>Ref: {{ $transaction->reference_number }}</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">
                                    Tidak ada transaksi pada tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
