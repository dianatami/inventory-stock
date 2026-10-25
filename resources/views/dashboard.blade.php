@extends('layouts.app')

@section('title', 'Dashboard - Inventori TPN')
@section('page-title', 'Dashboard')

@section('content')
    @if ($newOrders->isNotEmpty())
        <dialog class="dashboard-order-modal" data-order-notification>
            <div class="dashboard-order-modal-content">
                <button type="button" class="dashboard-order-modal-close" data-close-order-notification aria-label="Tutup">×</button>
                <span class="dashboard-order-modal-label">PESANAN BARU DARI OUTLET</span>
                <h2>{{ $newOrdersCount }} pesanan menunggu pemeriksaan</h2>
                <p>Ada pesanan outlet yang belum diperiksa dan disetujui.</p>
                <div class="dashboard-order-modal-list">
                    @foreach ($newOrders as $order)
                        <div>
                            <strong>{{ $order->outlet->name }}</strong>
                            <span>{{ $order->order_number }} · {{ $order->items->count() }} produk</span>
                        </div>
                        @if ($newOrdersCount > $newOrders->count())
                            <p>Menampilkan {{ $newOrders->count() }} pesanan terbaru.</p>
                        @endif
                    @endforeach
                </div>
                <a href="{{ route('warehouse.orders.index', ['status' => 'requested']) }}" class="btn btn-primary">Periksa Pesanan</a>
            </div>
        </dialog>
    @endif

    <div class="welcome-panel">
        <h2>Selamat datang, {{ auth()->user()->name }}!</h2>
        <p>
            Ringkasan inventori PT Tiga Pemuda Nusantara untuk membantu
            memantau persediaan dan aktivitas transaksi gudang.
        </p>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card">
            <span class="card-label">Total Produk</span>
            <h3>{{ number_format($metrics['products'], 0, ',', '.') }}</h3>
            <p>Produk terdaftar</p>
        </div>
        <div class="dashboard-card">
            <span class="card-label">Total Stok</span>
            <h3>{{ number_format($metrics['stock'], 0, ',', '.') }}</h3>
            <p>Unit seluruh produk</p>
        </div>
        <div class="dashboard-card dashboard-card-warning">
            <span class="card-label">Stok Menipis</span>
            <h3>{{ number_format($metrics['low_stock'], 0, ',', '.') }}</h3>
            <p>Produk pada/bawah batas minimum</p>
        </div>
        <div class="dashboard-card">
            <span class="card-label">Barang Masuk Hari Ini</span>
            <h3>{{ number_format($metrics['today_in'], 0, ',', '.') }}</h3>
            <p>Transaksi masuk</p>
        </div>
        <div class="dashboard-card">
            <span class="card-label">Barang Keluar Hari Ini</span>
            <h3>{{ number_format($metrics['today_out'], 0, ',', '.') }}</h3>
            <p>Transaksi keluar</p>
        </div>
        <div class="dashboard-card">
            <span class="card-label">Total Supplier</span>
            <h3>{{ number_format($metrics['suppliers'], 0, ',', '.') }}</h3>
            <p>Supplier terdaftar</p>
        </div>
        <div class="dashboard-card">
            <span class="card-label">Total Pengguna</span>
            <h3>{{ number_format($metrics['users'], 0, ',', '.') }}</h3>
            <p>Akun sistem</p>
        </div>
        <div class="dashboard-card">
            <span class="card-label">Jumlah Kategori</span>
            <h3>{{ number_format($metrics['categories'], 0, ',', '.') }}</h3>
            <p>Kategori produk</p>
        </div>
    </div>

    <section class="section-panel dashboard-chart">
        <div class="section-heading">
            <div>
                <h3>Grafik Transaksi Bulanan</h3>
                <p>Perbandingan jumlah transaksi masuk dan keluar selama 6 bulan.</p>
            </div>
            <div class="chart-legend">
                <span><i class="legend-in"></i> Masuk</span>
                <span><i class="legend-out"></i> Keluar</span>
            </div>
        </div>
        <div class="monthly-chart" role="img" aria-label="Grafik transaksi masuk dan keluar enam bulan terakhir">
            @foreach ($monthlyTransactions as $month)
                <div class="chart-month">
                    <div class="chart-bars">
                        <div class="chart-bar chart-bar-in"
                             style="height: {{ $month['incoming'] ? max(4, $month['incoming'] / $chartMax * 100) : 0 }}%"
                             title="Masuk: {{ $month['incoming'] }}"></div>
                        <div class="chart-bar chart-bar-out"
                             style="height: {{ $month['outgoing'] ? max(4, $month['outgoing'] / $chartMax * 100) : 0 }}%"
                             title="Keluar: {{ $month['outgoing'] }}"></div>
                    </div>
                    <span>{{ $month['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    @if (in_array(auth()->user()->role, ['Manager', 'Admin Prolog']))
        <section class="section-panel dashboard-alerts">
            <div class="section-heading">
                <div>
                    <h3>Alarm Stok Gudang</h3>
                    <p>Pemberitahuan aktif saat stok mencapai atau berada di bawah batas minimum.</p>
                </div>
                <a href="{{ route('notifications.low-stock') }}" class="section-link">Lihat semua</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Produk</th><th>Stok saat ini</th><th>Batas minimum</th><th>Waktu notifikasi</th></tr></thead>
                    <tbody>
                        @forelse ($activeNotifications as $notification)
                            <tr>
                                <td><strong>{{ $notification->product?->name ?? 'Produk tidak tersedia' }}</strong></td>
                                <td>{{ number_format($notification->current_stock, 0, ',', '.') }} {{ $notification->product?->unit }}</td>
                                <td>{{ number_format($notification->minimum_stock, 0, ',', '.') }} {{ $notification->product?->unit }}</td>
                                <td>{{ $notification->notified_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty-state">Tidak ada alarm stok gudang aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="dashboard-detail-grid">
        <section class="section-panel">
            <div class="section-heading">
                <div>
                    <h3>Riwayat Barang Masuk</h3>
                    <p>Transaksi masuk terbaru.</p>
                </div>
                @if (in_array(auth()->user()->role, ['Manager', 'Admin Prolog']))
                    <a href="{{ route('stock-in.index') }}" class="section-link">Lihat semua</a>
                @endif
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentStockIn as $transaction)
                            <tr>
                                <td>{{ $transaction->product?->name ?? 'Produk tidak tersedia' }}</td>
                                <td>{{ number_format($transaction->quantity, 0, ',', '.') }}
                                    {{ $transaction->product?->unit }}</td>
                                <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty-state">Belum ada transaksi barang masuk.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="section-panel">
            <div class="section-heading">
                <div>
                    <h3>Notifikasi Stok Menipis</h3>
                    <p><strong>{{ $metrics['low_stock'] }} produk</strong> berada pada atau di bawah batas minimum.</p>
                </div>
                <a href="{{ route('notifications.low-stock') }}" class="section-link">Tindak lanjuti</a>
            </div>
            @if ($metrics['low_stock'] > 0)
                <div class="dashboard-low-stock-warning" role="alert">
                    <strong>Perhatian: stok menipis</strong>
                    <span>Periksa produk berikut dan rencanakan pengadaan atau distribusi.</span>
                </div>
            @endif
            <div class="low-stock-list">
                @forelse ($lowStockProducts as $product)
                    <div class="low-stock-item">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <span>{{ $product->category?->name ?? 'Tanpa kategori' }}</span>
                        </div>
                        <div class="low-stock-amount">
                            <strong>{{ number_format($product->stock, 0, ',', '.') }} {{ $product->unit }}</strong>
                            <span>Minimum {{ number_format($product->minimum_stock, 0, ',', '.') }}</span>
                        </div>
                    </div>
                @empty
                    <p class="empty-state">Tidak ada produk dengan stok menipis.</p>
                @endforelse
            </div>
        </section>
    </div>
    @if ($newOrders->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const notification = document.querySelector('[data-order-notification]');
                const closeButton = document.querySelector('[data-close-order-notification]');
                if (notification && typeof notification.showModal === 'function') {
                    notification.showModal();
                    closeButton?.addEventListener('click', () => notification.close());
                }
            });
        </script>
    @endif
@endsection
