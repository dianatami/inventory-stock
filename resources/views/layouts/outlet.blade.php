<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Outlet - TPN')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .outlet-layout{min-height:100vh;display:grid;grid-template-columns:250px 1fr;background:#fffaf2}
        .outlet-sidebar{background:#35120f;color:#fff;padding:24px 16px;display:flex;flex-direction:column;gap:18px}
        .outlet-brand{padding:8px 12px 20px;border-bottom:1px solid #60302a}
        .outlet-brand strong{display:block;font-size:20px}.outlet-brand span{color:#f4c542;font-size:12px}
        .outlet-nav{display:grid;gap:6px}.outlet-nav a{padding:11px 12px;color:#f4e4db;border-radius:9px}
        .outlet-nav a:hover,.outlet-nav a.active{background:#63211b;color:#fff}
        .outlet-user{margin-top:auto;padding:14px 10px 0;border-top:1px solid #60302a;font-size:13px}
        .outlet-main{min-width:0}.outlet-topbar{display:flex;justify-content:space-between;align-items:center;padding:18px 28px;background:#fff;border-bottom:1px solid #eee2d2}
        .outlet-content{max-width:1280px;margin:auto;padding:28px}
        .outlet-page-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:22px}
        .outlet-page-head h1{margin:0 0 5px;font-size:25px}.outlet-page-head p{margin:0;color:#81716d}
        .outlet-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:22px}
        .outlet-stat{padding:20px;border-radius:14px;background:#fff;border:1px solid #eee2d2}
        .outlet-stat span{color:#81716d;font-size:13px}.outlet-stat strong{display:block;margin-top:8px;font-size:26px;color:#b42318}
        .outlet-panel{padding:20px;background:#fff;border:1px solid #eee2d2;border-radius:14px;margin-bottom:18px}
        .outlet-panel h2,.outlet-panel h3{margin:0 0 14px}.outlet-actions{display:flex;flex-wrap:wrap;gap:10px}
        .outlet-table-wrap{overflow-x:auto}.outlet-table{width:100%;border-collapse:collapse;min-width:650px}
        .outlet-table th,.outlet-table td{padding:12px;text-align:left;border-bottom:1px solid #eee2d2;vertical-align:top}
        .outlet-table th{background:#fff4dc;color:#5a251d;font-size:12px;text-transform:uppercase}
        .outlet-table input,.outlet-table select,.outlet-form input,.outlet-form select,.outlet-form textarea{width:100%;padding:10px 12px;border:1px solid #e5d6c6;border-radius:8px;background:white}
        .outlet-form{display:grid;gap:16px}.outlet-field{display:grid;gap:6px}.outlet-field label{font-weight:600}
        .outlet-form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px}
        .outlet-badge{display:inline-block;padding:4px 9px;border-radius:99px;background:#fff0c2;color:#7c4a00;font-size:12px;font-weight:700}
        .outlet-muted{color:#81716d}.outlet-alert{padding:12px 15px;border-radius:9px;margin-bottom:16px}
        .outlet-alert.success{background:#e8f6ed;color:#17643b}.outlet-alert.error{background:#fde8e7;color:#8f1d14}
        .outlet-alert.warning{background:#fff3cd;color:#705300}.outlet-empty{text-align:center;padding:28px;color:#81716d}
        .outlet-table .btn{white-space:nowrap}.outlet-small-form{min-width:250px}.outlet-footer{padding:16px 28px;color:#81716d;font-size:12px}
        @media(max-width:850px){.outlet-layout{grid-template-columns:1fr}.outlet-sidebar{padding:14px}.outlet-brand{padding:5px 8px 12px}.outlet-nav{grid-template-columns:repeat(2,minmax(0,1fr))}.outlet-user{margin-top:0}.outlet-content{padding:18px 12px}.outlet-topbar{padding:14px 16px}.outlet-page-head{align-items:flex-start;flex-direction:column}}
    </style>
</head>
<body>
<div class="outlet-layout">
    <aside class="outlet-sidebar">
        <div class="outlet-brand"><strong>TPN Outlet</strong><span>PORTAL PERSEDIAAN</span></div>
        <nav class="outlet-nav">
            <a href="{{ route('outlet.dashboard') }}" class="{{ request()->routeIs('outlet.dashboard') ? 'active' : '' }}">▦ Dashboard</a>
            <a href="{{ route('outlet.stocks.index') }}" class="{{ request()->routeIs('outlet.stocks.*') ? 'active' : '' }}">▤ Laporan Data Stok</a>
            <a href="{{ route('outlet.orders.index') }}" class="{{ request()->routeIs('outlet.orders.*') ? 'active' : '' }}">⇢ Pemesanan Barang</a>
            <a href="{{ route('outlet.returns.index') }}" class="{{ request()->routeIs('outlet.returns.*') ? 'active' : '' }}">↩ Retur Barang</a>
            <a href="{{ route('outlet.stocktake') }}" class="{{ request()->routeIs('outlet.stocktake*') ? 'active' : '' }}">± Stok Akhir Outlet</a>
            <a href="{{ route('outlet.usage.index') }}" class="{{ request()->routeIs('outlet.usage.*') ? 'active' : '' }}">◷ Barang Terpakai</a>
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
