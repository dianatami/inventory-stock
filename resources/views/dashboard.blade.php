
@extends('layouts.app')

@section('title', 'Dashboard - OMPROF Inventory')
@section('page-title', 'Dashboard')

@section('content')
    <div class="welcome-panel">
        <h2>Selamat datang, {{ auth()->user()->name }}!</h2>
        <p>
            Pantau dan kelola persediaan bahan serta kebutuhan produksi
            restoran melalui sistem inventori OMPROF.
        </p>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card">
            <span class="card-label">Total Produk</span>
            <h3>0</h3>
            <p>Produk terdaftar</p>
        </div>

        <div class="dashboard-card">
            <span class="card-label">Stok Menipis</span>
            <h3>0</h3>
            <p>Produk perlu diperiksa</p>
        </div>

        <div class="dashboard-card">
            <span class="card-label">Stok Masuk</span>
            <h3>0</h3>
            <p>Transaksi hari ini</p>
        </div>

        <div class="dashboard-card">
            <span class="card-label">Stok Keluar</span>
            <h3>0</h3>
            <p>Transaksi hari ini</p>
        </div>
    </div>

    <div class="section-panel">
        <h3>Ringkasan Inventori</h3>
        <p>
            Data statistik akan ditampilkan setelah fitur produk dan
            transaksi stok terhubung ke database.
        </p>
    </div>
@endsection
