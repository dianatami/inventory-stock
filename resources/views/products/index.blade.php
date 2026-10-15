@extends('layouts.app')

@section('title', 'Data Produk')

@section('content')
<div class="page-header">
    <div>
        <h1>Data Produk</h1>
        <p>Kelola data bahan baku dan barang inventori restoran.</p>
    </div>

    <a href="{{ route('products.create') }}" class="btn btn-primary">
        + Tambah Produk
    </a>
</div>

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="table-container">
    <form action="{{ route('products.index') }}" method="GET"
          class="filter-form">

        <div class="form-group">
            <label for="search">Cari Produk</label>
            <input
                type="text"
                id="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari kode atau nama produk..."
            >
        </div>

        <div class="form-group">
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id">
                <option value="">Semua Kategori</option>

                @foreach ($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        {{ request('category_id') == $category->id ? 'selected' : '' }}
                    >
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="status">Status Produk</label>
            <select id="status" name="status">
                <option value="">Semua Status</option>
                <option value="active"
                    {{ request('status') === 'active' ? 'selected' : '' }}>
                    Aktif
                </option>
                <option value="inactive"
                    {{ request('status') === 'inactive' ? 'selected' : '' }}>
                    Tidak Aktif
                </option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                Cari
            </button>

            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                Reset
            </a>
        </div>
    </form>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Supplier</th>
                    <th>Satuan</th>
                    <th>Stok</th>
                    <th>Stok Minimum</th>
                    <th>Status Stok</th>
                    <th>Status Produk</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            {{ $products->firstItem() + $loop->index }}
                        </td>

                        <td>{{ $product->code }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category->name }}</td>
                        <td>{{ $product->supplier->name ?? '-' }}</td>
                        <td>{{ $product->unit }}</td>
                        <td>{{ number_format($product->stock) }}</td>
                        <td>{{ number_format($product->minimum_stock) }}</td>

                        <td>
                            @if ($product->stock <= $product->minimum_stock)
                                <span class="status-badge status-low">
                                    Stok Rendah
                                </span>
                            @else
                                <span class="status-badge status-safe">
                                    Aman
                                </span>
                            @endif
                        </td>

                        <td>
                            @if ($product->is_active)
                                <span class="status-badge status-safe">
                                    Aktif
                                </span>
                            @else
                                <span class="status-badge status-low">
                                    Tidak Aktif
                                </span>
                            @endif
                        </td>

                        <td>
                            <div class="action-buttons">
                                <a
                                    href="{{ route('products.edit', $product->id) }}"
                                    class="btn btn-secondary"
                                >
                                    Edit
                                </a>

                                <form
                                    action="{{ route('products.destroy', $product->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus produk ini?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                    >
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="empty-state">
                            Belum ada data produk.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        {{ $products->links() }}
    </div>
</div>
@endsection
