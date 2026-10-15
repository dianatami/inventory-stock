
@extends('layouts.app')

@section('title', 'Mutasi Stok - OMPROF')
@section('page-title', 'Mutasi Stok')

@section('content')
<div class="container">

    {{-- HEADER --}}
    <div class="page-header">
        <div>
            <h1>Mutasi Stok</h1>
            <p>Ringkasan seluruh pergerakan stok masuk dan keluar.</p>
        </div>
    </div>

    {{-- PESAN --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">▤</div>
            <div>
                <p>Total Transaksi</p>
                <h2>{{ number_format($totalTransactions, 0, ',', '.') }}</h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">↓</div>
            <div>
                <p>Total Masuk</p>
                <h2 style="color: #16a34a;">
                    +{{ number_format($totalIn, 0, ',', '.') }}
                </h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">↑</div>
            <div>
                <p>Total Keluar</p>
                <h2 style="color: #dc2626;">
                    -{{ number_format($totalOut, 0, ',', '.') }}
                </h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">▥</div>
            <div>
                <p>Net Flow</p>
                <h2 style="color: {{ $netFlow >= 0 ? '#16a34a' : '#dc2626' }};">
                    {{ $netFlow > 0 ? '+' : '' }}{{ number_format($netFlow, 0, ',', '.') }}
                </h2>
            </div>
        </div>

    </div>

    {{-- FILTER --}}
    <div class="card">
        <form action="{{ route('stock-mutations.index') }}" method="GET">

            <div class="filter-row">

                <div class="form-group">
                    <label for="search">Cari Produk</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        placeholder="Nama atau kode produk..."
                    >
                </div>

                <div class="form-group">
                    <label for="type">Jenis</label>
                    <select name="type" id="type">
                        <option value="">Semua</option>
                        <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>
                            Masuk
                        </option>
                        <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>
                            Keluar
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="start_date">Dari Tanggal</label>
                    <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        value="{{ request('start_date') }}"
                    >
                </div>

                <div class="form-group">
                    <label for="end_date">Sampai Tanggal</label>
                    <input
                        type="date"
                        name="end_date"
                        id="end_date"
                        value="{{ request('end_date') }}"
                    >
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Cari
                </button>

                <a href="{{ route('stock-mutations.index') }}"
                   class="btn btn-secondary">
                    Reset
                </a>
            </div>

        </form>
    </div>

    {{-- TABEL RIWAYAT --}}
    <div class="card">

        <div class="page-header">
            <h2>Riwayat Pergerakan Stok</h2>

            <span class="badge">
                {{ $transactions->total() }} transaksi ditemukan
            </span>
        </div>

        <div class="table-responsive">
            <table class="table">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Produk</th>
                        <th>Kuantitas</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>
                                #{{ $transactions->firstItem() + $loop->index }}
                            </td>

                            <td>
                                {{ $transaction->transaction_date
                                    ? $transaction->transaction_date->format('d M Y')
                                    : '-' }}
                            </td>

                            <td>
                                @if ($transaction->type === 'in')
                                    <span class="quantity-in">● MASUK</span>
                                @else
                                    <span class="quantity-out">● KELUAR</span>
                                @endif
                            </td>

                            <td>
                                <strong>
                                    {{ $transaction->product->code ?? '-' }}
                                </strong>
                                <br>
                                <small>
                                    {{ $transaction->product->name ?? 'Produk tidak tersedia' }}
                                </small>
                            </td>

                            <td>
                                @if ($transaction->type === 'in')
                                    <span class="quantity-in">
                                        +{{ number_format($transaction->quantity, 0, ',', '.') }}
                                        {{ $transaction->product->unit ?? '' }}
                                    </span>
                                @else
                                    <span class="quantity-out">
                                        -{{ number_format($transaction->quantity, 0, ',', '.') }}
                                        {{ $transaction->product->unit ?? '' }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                {{ $transaction->notes ?: '-' }}

                                @if ($transaction->product && $transaction->product->supplier)
                                    <br>
                                    <small>
                                        Supplier: {{ $transaction->product->supplier->name }}
                                    </small>
                                @endif

                                @if ($transaction->reference_number)
                                    <br>
                                    <small>
                                        Referensi: {{ $transaction->reference_number }}
                                    </small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                Belum ada transaksi stok masuk atau keluar.
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
