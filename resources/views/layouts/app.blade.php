
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'OMPROF Inventory')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .company-sidebar-logo{display:block;width:60px;height:60px;flex:0 0 auto;object-fit:contain}
        .notification-menu{position:relative;z-index:160}.notification-toggle{position:relative;width:42px;height:42px;border:1px solid var(--border);border-radius:50%;background:#fff;color:var(--text-main);font-size:20px}
        .topbar-actions{display:flex;flex:0 0 auto;align-items:center;justify-content:flex-end;gap:24px;margin-left:auto}
        .notification-count{position:absolute;top:-5px;right:-5px;min-width:19px;padding:2px 5px;border-radius:99px;background:#dc2626;color:#fff;font-size:10px;font-weight:700}
        .notification-panel{position:absolute;z-index:170;top:calc(100% + 12px);right:0;width:min(380px,calc(100vw - 24px));padding:0;border:1px solid var(--border);border-radius:12px;background:#fff;box-shadow:0 12px 32px rgb(31 15 10 / 15%)}
        .notification-panel[hidden]{display:none}.notification-panel-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid var(--border)}
        .notification-panel-heading button,.notification-read-action{border:0;background:none;color:var(--primary);font-size:12px;font-weight:600;cursor:pointer}
        .notification-items{max-height:380px;overflow:auto}.notification-item{display:grid;grid-template-columns:32px 1fr;gap:10px;padding:13px 16px;border-bottom:1px solid #f1e9e2}
        .notification-item.unread{background:#fffaf2}.notification-item-link{display:grid;grid-template-columns:32px 1fr;gap:10px;color:inherit}
        .notification-item-icon{display:grid;place-items:center;width:30px;height:30px;border-radius:50%;background:#fff1d6;color:#8a5400;font-weight:700}
        .notification-item-icon.critical,.notification-item-icon.out{background:#fee2e2;color:#b42318}.notification-item-icon.info{background:#e8f0fe;color:#2456a6}.notification-item-icon.warning{background:#fff1d6;color:#8a5400}.notification-item strong{font-size:13px}.notification-item p{margin:3px 0;color:var(--text-muted);font-size:12px}
        .notification-item small{color:var(--text-muted);font-size:11px}.notification-panel-empty{padding:20px;text-align:center;color:var(--text-muted);font-size:13px}
        .notification-center-link{display:block;padding:13px;text-align:center;border-top:1px solid var(--border);font-size:12px;font-weight:600}
        .distribution-detail{min-width:300px;padding:12px 0}.notification-read-button{border:0;background:transparent;color:var(--primary);font-weight:600;cursor:pointer}
        .status-danger{background:#fee2e2;color:#991b1b}
        .user-profile{position:relative;flex:0 0 auto}
        .user-profile-trigger{display:flex;align-items:center;gap:10px;padding:5px 10px 5px 5px;border:1px solid transparent;border-radius:12px;background:#fff;color:var(--text-main);cursor:pointer;list-style:none}
        .user-profile-trigger::-webkit-details-marker{display:none}
        .user-profile[open] .user-profile-trigger,.user-profile-trigger:hover{border-color:var(--border);background:#fffaf2}
        .user-profile .user-menu{gap:10px}
        .user-profile .user-info{min-width:0;max-width:180px}
        .user-profile .user-info strong,.user-profile .user-info span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .user-profile-chevron{color:var(--muted);font-size:11px}
        .user-profile-dropdown{position:absolute;z-index:180;top:calc(100% + 8px);right:0;width:220px;padding:12px;border:1px solid var(--border);border-radius:12px;background:#fff;box-shadow:0 12px 32px rgb(31 15 10 / 15%)}
        .user-profile-dropdown strong,.user-profile-dropdown span{display:block}
        .user-profile-dropdown strong{font-size:13px}
        .user-profile-dropdown span{margin-top:3px;color:var(--muted);font-size:12px}
        .user-profile-dropdown form{margin:10px 0 0;padding-top:10px;border-top:1px solid var(--border)}
        .user-profile-dropdown .logout-button{width:100%;text-align:left}
        @media(max-width:900px){.topbar-actions{gap:24px}}
        @media(max-width:600px){.notification-panel{right:-45px}.user-profile .user-info{display:none}.user-profile-trigger{padding-right:6px}.topbar-actions{gap:12px}.topbar{gap:8px}.topbar-title h1{max-width:32vw;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}}
    </style>
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar" id="sidebar" data-sidebar>
            <div class="sidebar-brand">
                @if (in_array(auth()->user()->role, ['Manager', 'Admin Prolog', 'Kepala Logistik'], true))
                    <img class="company-sidebar-logo"
                         src="{{ asset('images/logo-perusahaan.jpeg') }}"
                         alt="Logo PT Tiga Pemuda Nusantara">
                @endif
                <div>
                    <h2>TPN</h2>
                    <p>Inventory System</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <p class="nav-heading">MENU UTAMA</p>

                <a href="{{ route('dashboard') }}"
                   class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span>▦</span> Dashboard
                </a>

                <p class="nav-heading">MASTER DATA</p>

                <a href="{{ route('categories.index') }}"
                   class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    <span>▤</span> Kategori
                </a>

                @if (auth()->user()->role === 'Manager')
                    <a href="{{ route('suppliers.index') }}"
                       class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                        <span>♧</span> Supplier
                    </a>
                    <a href="{{ route('products.index') }}"
                       class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <span>▣</span> Produk
                    </a>
                @endif

                @if (in_array(auth()->user()->role, ['Manager', 'Admin Prolog']))
                    <p class="nav-heading">TRANSAKSI STOK</p>

                    <a href="{{ route('stock-in.index') }}"
                       class="nav-link {{ request()->routeIs('stock-in.*') ? 'active' : '' }}">
                        <span>↓</span> Stok Masuk
                    </a>

                    <a href="{{ route('stock-out.index') }}"
                       class="nav-link {{ request()->routeIs('stock-out.*') ? 'active' : '' }}">
                        <span>↑</span> Stok Keluar
                    </a>

                    <a href="{{ route('warehouse.orders.index') }}"
                       class="nav-link {{ request()->routeIs('warehouse.orders.*') ? 'active' : '' }}">
                        <span>⇢</span> Pesanan Outlet
                    </a>

                    <a href="{{ route('distributions.index') }}"
                       class="nav-link {{ request()->routeIs('distributions.*') ? 'active' : '' }}">
                        <span>↗</span> Distribusi Barang
                    </a>
                    <a href="{{ route('warehouse.receipts.index') }}"
                       class="nav-link {{ request()->routeIs('warehouse.receipts.*') ? 'active' : '' }}">
                        <span>✓</span> Validasi Penerimaan
                    </a>

                    <a href="{{ route('stock-adjustments.index') }}"
                       class="nav-link {{ request()->routeIs('stock-adjustments.*') ? 'active' : '' }}">
                        <span>±</span> Penyesuaian Stok
                    </a>

                    <a href="{{ route('stock-mutations.index') }}"
                       class="nav-link {{ request()->routeIs('stock-mutations.*') ? 'active' : '' }}">
                        <span>⇄</span> Mutasi Stok
                    </a>
                @endif
                @if (in_array(auth()->user()->role, ['Manager', 'Admin Prolog', 'Kepala Logistik']))
                    <a href="{{ route('warehouse.returns.index') }}"
                       class="nav-link {{ request()->routeIs('warehouse.returns.*') ? 'active' : '' }}">
                        <span>↩</span> Retur Outlet
                    </a>
                @endif

                <p class="nav-heading">ADMIN & DATA</p>

                <a href="{{ route('reports.stock') }}"
                   class="nav-link {{ request()->routeIs('reports.stock') ? 'active' : '' }}">
                    <span>▥</span> Laporan Stok Keseluruhan
                </a>

                <a href="{{ route('reports.daily') }}"
                   class="nav-link {{ request()->routeIs('reports.daily') ? 'active' : '' }}">
                    <span>◷</span> Laporan Stok Harian
                </a>

                <a href="{{ route('statistics.index') }}"
                   class="nav-link {{ request()->routeIs('statistics.*') ? 'active' : '' }}">
                    <span>▥</span> Statistik Data
                </a>

                <p class="nav-heading">PENGATURAN</p>
                <a href="{{ route('settings.index') }}"
                   class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <span>⚙</span> Pengaturan Profil
                </a>
                @if (auth()->user()->role === 'Manager')
                    <a href="{{ route('outlets.index') }}"
                       class="nav-link {{ request()->routeIs('outlets.*') ? 'active' : '' }}">
                        <span>⌂</span> Data Outlet
                    </a>
                    <a href="{{ route('users.index') }}"
                       class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <span>♙</span> Manajemen Pengguna
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <span class="status-dot"></span>
                Sistem Inventori Aktif
            </div>
        </aside>

        <div class="main-area">
            <header class="topbar">
                <button type="button"
                        class="menu-toggle"
                        data-menu-toggle
                        aria-label="Ciutkan sidebar"
                        aria-controls="sidebar"
                        aria-expanded="true">
                    ☰
                </button>

                <div class="topbar-title">
                    <h1>@yield('page-title', 'Dashboard')</h1>
                    <p>Sistem Informasi Inventori dan Distribusi Barang</p>
                </div>

                <div class="topbar-actions">
                    @if (in_array(auth()->user()->role, ['Manager', 'Admin Prolog', 'Kepala Logistik'], true))
                        <div class="notification-menu" data-notifications
                             data-feed="{{ route('notifications.feed') }}"
                             data-read-all="{{ route('notifications.read-all') }}">
                            <button type="button" class="notification-toggle" data-notification-toggle
                                    aria-label="Notifikasi" aria-expanded="false">
                                <svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>
                                </svg>
                                <span class="notification-count" data-notification-count hidden>0</span>
                            </button>
                            <section class="notification-panel" data-notification-panel hidden>
                                <div class="notification-panel-heading">
                                    <strong>Notifikasi terbaru</strong>
                                    <button type="button" data-notification-read-all>Tandai semua dibaca</button>
                                </div>
                                <div class="notification-items" data-notification-items aria-live="polite"></div>
                                <a class="notification-center-link" href="{{ route('notifications.low-stock') }}">Lihat Semua Notifikasi</a>
                            </section>
                        </div>
                    @endif

                    <details class="user-profile" data-profile-menu>
                        <summary class="user-profile-trigger" aria-label="Buka menu profil">
                            <span class="user-menu">
                                <span class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                <span class="user-info">
                                    <strong>{{ auth()->user()->name }}</strong>
                                    <span>{{ auth()->user()->role }}</span>
                                </span>
                            </span>
                            <span class="user-profile-chevron" aria-hidden="true">▼</span>
                        </summary>
                        <div class="user-profile-dropdown">
                            <strong>{{ auth()->user()->name }}</strong>
                            <span>{{ auth()->user()->role }}</span>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="logout-button">Logout</button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <main class="page-content">
                @yield('content')
            </main>

            <footer class="page-footer">
                &copy; {{ date('Y') }} PT Tiga Pemuda Nusantara
            </footer>
        </div>
    </div>
</body>
</html>
