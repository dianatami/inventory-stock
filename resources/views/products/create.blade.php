```blade
@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('content')
<div class="page-header">
    <div>
        <h1>Tambah Produk</h1>
        <p>Tambahkan data bahan baku atau barang inventori baru.</p>
    </div>

    <a href="{{ route('products.index') }}" class="btn btn-secondary">
        Kembali
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-container">
    <form action="{{ route('products.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="code">Kode Produk *</label>
            <input
                type="text"
                id="code"
                name="code"
                value="{{ old('code') }}"
                placeholder="Contoh: BB001"
                maxlength="255"
                required
            >
            @error('code')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="name">Nama Produk *</label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name') }}"
                placeholder="Masukkan nama produk"
                maxlength="255"
                required
            >
            @error('name')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="category_id">Kategori *</label>
            <select id="category_id" name="category_id" required>
                <option value="">-- Pilih Kategori --</option>

                @foreach ($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        {{ old('category_id') == $category->id ? 'selected' : '' }}
                    >
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            @error('category_id')
                <small class="error-message">{{ $message }}</small>
            @enderror

            @if ($categories->isEmpty())
                <small class="error-message">
                    Belum ada kategori. Tambahkan kategori terlebih dahulu.
                </small>
            @endif
        </div>

        <div class="form-group">
            <label for="supplier_id">Supplier</label>
            <select id="supplier_id" name="supplier_id">
                <option value="">-- Pilih Supplier (Opsional) --</option>

                @foreach ($suppliers as $supplier)
                    <option
                        value="{{ $supplier->id }}"
                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}
                    >
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>

            @error('supplier_id')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="unit">Satuan *</label>
            <input
                type="text"
                id="unit"
                name="unit"
                value="{{ old('unit') }}"
                placeholder="Contoh: kg, liter, pcs, dus"
                maxlength="30"
                required
            >
            @error('unit')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="stock">Stok Awal *</label>
            <input
                type="number"
                id="stock"
                name="stock"
                value="{{ old('stock', 0) }}"
                min="0"
                step="1"
                required
            >
            @error('stock')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="minimum_stock">Stok Minimum *</label>
            <input
                type="number"
                id="minimum_stock"
                name="minimum_stock"
                value="{{ old('minimum_stock', 0) }}"
                min="0"
                step="1"
                required
            >
            @error('minimum_stock')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea
                id="description"
                name="description"
                rows="4"
                placeholder="Keterangan tambahan produk (opsional)"
            >{{ old('description') }}</textarea>

            @error('description')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="is_active">Status Produk *</label>
            <select id="is_active" name="is_active" required>
                <option value="1"
                    {{ old('is_active', '1') == '1' ? 'selected' : '' }}>
                    Aktif
                </option>
                <option value="0"
                    {{ old('is_active') === '0' ? 'selected' : '' }}>
                    Tidak Aktif
                </option>
            </select>

            @error('is_active')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Simpan Produk
            </button>

            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
```