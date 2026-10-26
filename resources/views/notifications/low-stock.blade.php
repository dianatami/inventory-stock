@extends('layouts.app')

@section('title', 'Pusat Notifikasi')
@section('page-title', 'Pusat Notifikasi')

@section('content')
<div class="notification-center container">
    <div class="notification-breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span>›</span>Notifikasi</div>
    <div class="page-header notification-page-header">
        <div>
            <h1>Pusat Notifikasi Sistem</h1>
            <p>Periksa pembaruan stok dan aktivitas transaksi yang perlu diketahui.</p>
        </div>
        <div class="notification-page-actions">
            <a class="btn btn-secondary" href="{{ route('notifications.low-stock', request()->query()) }}" aria-label="Muat ulang notifikasi">↻ Refresh</a>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn btn-primary" type="submit">Tandai Semua Dibaca</button>
            </form>
        </div>
    </div>

    <section class="notification-banner">
        <div class="notification-banner-copy">
            <span class="notification-banner-eyebrow">PEMANTAUAN INVENTORI</span>
            <h2>Pantau Semua Pembaruan Stok &amp; Aktivitas</h2>
            <p>Dapatkan peringatan terkait perubahan persediaan, distribusi barang, pemesanan, dan retur secara otomatis.</p>
        </div>
        <div class="notification-banner-stats">
            <div><strong>{{ number_format($totalCount, 0, ',', '.') }}</strong><span>Total notifikasi</span></div>
            <div><strong>{{ number_format($unreadCount, 0, ',', '.') }}</strong><span>Belum dibaca</span></div>
            <div><strong>{{ number_format($stockCount, 0, ',', '.') }}</strong><span>Stok menipis / kritis</span></div>
        </div>
    </section>

    <div class="notification-center-grid">
        <section class="notification-list-column">
            <div class="notification-filter-card">
                <nav class="notification-tabs" aria-label="Filter kategori notifikasi">
                    @foreach ([
                        'all' => 'Semua',
                        'unread' => 'Belum Dibaca',
                        'stock_critical' => 'Stok Kritis',
                        'purchase_order' => 'Pemesanan',
                        'distribution' => 'Distribusi',
                        'return' => 'Retur',
                    ] as $tab => $label)
                        <a class="{{ $category === $tab ? 'active' : '' }}"
                           href="{{ route('notifications.low-stock', array_merge(request()->except('page'), ['category' => $tab])) }}">{{ $label }}</a>
                    @endforeach
                </nav>
                <form class="notification-search" method="GET" action="{{ route('notifications.low-stock') }}">
                    <input type="hidden" name="category" value="{{ $category }}">
                    <label class="sr-only" for="notification-search">Cari notifikasi</label>
                    <input class="form-control" type="search" id="notification-search" name="search"
                           value="{{ request('search') }}" placeholder="Cari produk, SKU, transaksi, atau pesan...">
                    <button class="btn btn-primary" type="submit">Cari</button>
                </form>
            </div>

            <div class="notification-list-heading">
                <div><h2>Notifikasi</h2><p>{{ number_format($notifications->total(), 0, ',', '.') }} hasil</p></div>
            </div>

            <div class="notification-cards">
                @forelse ($notifications as $notification)
                    @php
                        $readUrl = $notification['type'] === 'stock'
                            ? route('notifications.read', $notification['id'])
                            : route('notifications.activity.read', $notification['id']);
                    @endphp
                    <article class="notification-center-card tone-{{ $notification['tone'] }} {{ $notification['read'] ? '' : 'is-unread' }}">
                        <div class="notification-center-icon" aria-hidden="true">{{ $notification['icon'] }}</div>
                        <div class="notification-center-content">
                            <div class="notification-card-meta">
                                <span class="notification-category-badge">{{ $notification['category_label'] }}</span>
                                <time>{{ $notification['created_at'] }}</time>
                            </div>
                            <h3>{{ $notification['title'] }}</h3>
                            <p>{{ $notification['message'] }}</p>
                            <div class="notification-card-details">
                                @if ($notification['reference_number'])
                                    <span>Referensi: <strong>{{ $notification['reference_number'] }}</strong></span>
                                @endif
                                @if ($notification['location'])
                                    <span>Lokasi: <strong>{{ $notification['location'] }}</strong></span>
                                @endif
                                <span class="notification-read-state">{{ $notification['read'] ? 'Sudah dibaca' : 'Belum dibaca' }}</span>
                            </div>
                            <div class="notification-card-actions">
                                <a href="{{ $notification['action_url'] }}">{{ $notification['action_label'] }} <span aria-hidden="true">→</span></a>
                                @unless ($notification['read'])
                                    <form method="POST" action="{{ $readUrl }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit">Tandai Dibaca</button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="notification-center-empty">
                        <span aria-hidden="true">✓</span>
                        <h3>Tidak ada notifikasi</h3>
                        <p>Notifikasi yang sesuai dengan filter ini akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>
            <div class="pagination-wrapper">{{ $notifications->links() }}</div>
        </section>

        <aside class="notification-summary-column">
            <section class="notification-summary-card">
                <div class="notification-summary-heading"><span>◉</span><div><h2>Ringkasan Alert Gudang</h2><p>Status yang memerlukan perhatian</p></div></div>
                <div class="notification-summary-metrics">
                    <div><strong class="text-danger">{{ number_format($criticalCount, 0, ',', '.') }}</strong><span>Stok kritis / habis</span></div>
                    <div><strong>{{ number_format($pendingOrders, 0, ',', '.') }}</strong><span>Pemesanan menunggu</span></div>
                    <div><strong>{{ number_format($processingReturns, 0, ',', '.') }}</strong><span>Retur diproses</span></div>
                </div>
            </section>

            @if ($canViewStock)
                <section class="notification-summary-card">
                    <div class="notification-summary-heading"><span>↗</span><div><h2>Prioritas Restok</h2><p>Stok saat ini dibandingkan batas minimum</p></div></div>
                    <div class="restock-priority-list">
                        @forelse ($restockPriorities as $product)
                            <div class="restock-priority-item">
                                <div><strong>{{ $product->name }}</strong><small>{{ $product->code }}</small></div>
                                <span class="{{ $product->stock === 0 ? 'text-danger' : 'text-warning' }}">{{ $product->stock }} / {{ $product->minimum_stock }} {{ $product->unit }}</span>
                            </div>
                        @empty
                            <p class="notification-summary-empty">Tidak ada produk di bawah batas minimum.</p>
                        @endforelse
                    </div>
                </section>
            @endif

            <section class="notification-summary-note">
                <strong>Informasi</strong>
                <p>Data diperbarui dari aktivitas transaksi dan pemeriksaan stok terakhir di sistem.</p>
            </section>
        </aside>
    </div>
</div>
@endsection
