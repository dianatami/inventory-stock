
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'OMPROF Inventory')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">O</div>
                <div>
                    <h2>OMPROF</h2>
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

                <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                    <span>♧</span> Supplier
                </a>
                <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <span>▣</span> Produk
                </a>

                <p class="nav-heading">TRANSAKSI STOK</p>

               
                @if (auth()->check() && in_array(auth()->user()->role, ['Manager', 'Admin Prolog']))
                    <a href="{{ route('stock-in.index') }}"
                    class="nav-link {{ request()->routeIs('stock-in.*') ? 'active' : '' }}">
                        <span>↓</span> Stok Masuk
                    </a>

                    <a href="{{ route('stock-out.index') }}"
                    class="nav-link {{ request()->routeIs('stock-out.*') ? 'active' : '' }}">
                        <span>↑</span> Stok Keluar
                    </a>

                    <a href="{{ route('stock-mutations.index') }}"
                    class="nav-link {{ request()->routeIs('stock-mutations.*') ? 'active' : '' }}">
                        <span>⇄</span> Mutasi Stok
                    </a>
                @endif

                <p class="nav-heading">LAINNYA</p>

                <a href="{{ route('reports.stock') }}"
                class="nav-link {{ request()->routeIs('reports.stock') ? 'active' : '' }}">
                    <span>▥</span> Laporan Stok
                </a>

                @if (auth()->user()->role === 'Manager')
                    <a href="{{ route('users.index') }}" class="nav-link">
                        <span>♙</span> Pengguna
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
                        aria-label="Buka menu">
                    ☰
                </button>

                <div class="topbar-title">
                    <h1>@yield('page-title', 'Dashboard')</h1>
                    <p>Sistem Pengelolaan Inventori Produksi Restoran</p>
                </div>

                <div class="user-menu">
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>

                    <div class="user-info">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ auth()->user()->role }}</span>
                    </div>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="logout-button">
                            Logout
                        </button>
                    </form>
                </div>
            </header>

            <main class="page-content">
                @yield('content')
            </main>

            <footer class="page-footer">
                &copy; {{ date('Y') }} PT OMPROF — Inventory Management
            </footer>
        </div>
    </div>
</body>
</html>
