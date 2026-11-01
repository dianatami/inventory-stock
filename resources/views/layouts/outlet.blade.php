<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Outlet - TPN')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@include('components.icon-sprite')
<div class="outlet-layout">
    <aside class="outlet-sidebar">
        <div class="outlet-brand">
            <div class="outlet-brand-heading">
                <span class="outlet-brand-mark"><svg class="interface-icon" aria-hidden="true"><use href="#icon-package"/></svg></span>
                <strong>TPN Outlet</strong>
            </div>
            <span>PORTAL PERSEDIAAN</span>
        </div>
        <nav class="outlet-nav">
            <a href="{{ route('outlet.dashboard') }}" class="{{ request()->routeIs('outlet.dashboard') ? 'active' : '' }}"><svg class="nav-icon" aria-hidden="true"><use href="#icon-dashboard"/></svg> Dashboard</a>
            <a href="{{ route('outlet.stocks.index') }}" class="{{ request()->routeIs('outlet.stocks.*') ? 'active' : '' }}"><svg class="nav-icon" aria-hidden="true"><use href="#icon-layers"/></svg> Laporan Data Stok</a>
            <a href="{{ route('outlet.orders.index') }}" class="{{ request()->routeIs('outlet.orders.*') ? 'active' : '' }}"><svg class="nav-icon" aria-hidden="true"><use href="#icon-shopping-cart"/></svg> Pemesanan Barang</a>
            <a href="{{ route('outlet.returns.index') }}" class="{{ request()->routeIs('outlet.returns.*') ? 'active' : '' }}"><svg class="nav-icon" aria-hidden="true"><use href="#icon-rotate"/></svg> Retur Barang</a>
            <a href="{{ route('outlet.stocktake') }}" class="{{ request()->routeIs('outlet.stocktake*') ? 'active' : '' }}"><svg class="nav-icon" aria-hidden="true"><use href="#icon-adjustments"/></svg> Stok Akhir Outlet</a>
            <a href="{{ route('outlet.usage.index') }}" class="{{ request()->routeIs('outlet.usage.*') ? 'active' : '' }}"><svg class="nav-icon" aria-hidden="true"><use href="#icon-chart"/></svg> Barang Terpakai</a>
        </nav>
        <div class="outlet-user">
            <strong>{{ auth()->user()->name }}</strong><br>
            <span class="outlet-muted" style="color:#d9beb4">{{ $outlet->name ?? auth()->user()->outlet?->name }}</span>
            <form action="{{ route('logout') }}" method="POST" style="margin-top:12px">@csrf
                <button type="submit" class="btn" style="background:#f4c542;color:#35120f">Keluar</button>
            </form>
        </div>
    </aside>
    <div class="outlet-main">
        <header class="outlet-topbar"><strong>@yield('page-title', 'Dashboard Outlet')</strong><span class="outlet-muted">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span></header>
        <main class="outlet-content">
            @if (session('success'))<div class="outlet-alert success">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="outlet-alert error">{{ session('error') }}</div>@endif
            @if ($errors->any())
                <div class="outlet-alert error"><strong>Periksa kembali data:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </main>
        <footer class="outlet-footer">&copy; {{ date('Y') }} PT Tiga Pemuda Nusantara</footer>
    </div>
</div>
</body>
</html>
