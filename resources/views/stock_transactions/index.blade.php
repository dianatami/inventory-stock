
@extends('layouts.app')

@section('title', $pageType . ' - OMPROF')
@section('page-title', $pageType)

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>{{ $pageType }}</h1>
            <p>
                @if ($type === 'in')
                    Riwayat penerimaan stok produk.
                @else
                    Riwayat pengeluaran stok produk.
                @endif
            </p>
        </div>

        <a href="{{ $type === 'in' ? route('stock-in.create') : route('stock-out.create') }}"
           class="btn btn-primary">
            + Input {{ $pageType }}
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- KARTU STATISTIK --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">▤</div>
            <div>
                <p>Total Transaksi</p>
                <h2>{{ number_format($totalTransactions, 0, ',', '.') }}</h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">◷</div>
            <div>
                <p>{{ $pageType }} Hari Ini</p>
                <h2>{{ number_format($todayTransactions, 0, ',', '.') }}</h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">▦</div>
            <div>
                <p>{{ $pageType }} Bulan Ini</p>
                <h2>{{ number_format($monthTransactions, 0, ',', '.') }}</h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">{{ $type === 'in' ? '↓' : '↑' }}</div>
            <div>
                <p>Total Qty {{ $type === 'in' ? 'Masuk' : 'Keluar' }}</p>
                <h2>{{ number_format($totalQuantity, 0, ',', '.') }}</h2>
            </div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="card">
        <form action="{{ $type === 'in' ? route('stock-in.index') : route('stock-out.index') }}"
              method="GET">
            <div class="filter-row">
                <div class="form-group">
                    <label for="search">Cari Produk</label>
                    <input type="text"
                           name="search"
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="Nama atau kode produk">
                </div>

                <div class="form-group">
                    <label for="start_date">Dari Tanggal</label>
                    <input type="date"
                           name="start_date"
                           id="start_date"
                           value="{{ request('start_date') }}">
                </div>

                <div class="form-group">
                    <label for="end_date">Sampai Tanggal</label>
                    <input type="date"
                           name="end_date"
                           id="end_date"
                           value="{{ request('end_date') }}">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Cari</button>
                <a href="{{ $type === 'in' ? route('stock-in.index') : route('stock-out.index') }}"
                   class="btn btn-secondary">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- RIWAYAT TRANSAKSI --}}
    <div class="card">
        <div class="page-header">
            <div>
                <h2>Riwayat {{ $pageType }}</h2>
            </div>
            <span class="badge">
                {{ $transactions->total() }} transaksi
            </span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Tanggal</th>
                        <th>Produk</th>
                        <th>Supplier</th>
                        <th>Jumlah</th>
                        <th>Stok Sebelum</th>
                        <th>Stok Sesudah</th>
                        <th>Pengguna</th>
                        <th>Catatan</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transactions->firstItem() + $loop->index }}</td>

                            <td>
                                {{ $transaction->transaction_date
                                    ? $transaction->transaction_date->format('d M Y H:i')
                                    : '-' }}
                            </td>

                            <td>
                                <strong>{{ $transaction->product->code ?? '-' }}</strong>
                                <br>
                                <small>{{ $transaction->product->name ?? 'Produk tidak tersedia' }}</small>
                            </td>

                            <td>
                                {{ $transaction->product->supplier->name ?? '-' }}
                            </td>

                            <td>
                                <span class="{{ $type === 'in' ? 'quantity-in' : 'quantity-out' }}">
                                    {{ $type === 'in' ? '+' : '-' }}{{ number_format($transaction->quantity, 0, ',', '.') }}
                                    {{ $transaction->product->unit ?? '' }}
                                </span>
                            </td>

                            <td>{{ number_format($transaction->stock_before, 0, ',', '.') }}</td>

                            <td>{{ number_format($transaction->stock_after, 0, ',', '.') }}</td>

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
                                Belum ada riwayat {{ strtolower($pageType) }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
