@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
<div class="page-header">
    <div>
        <h1>Edit Produk</h1>
        <p>Perbarui informasi bahan baku atau barang inventori.</p>
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
    <form action="{{ route('products.update', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="code">Kode Produk *</label>
            <input
                type="text"
                id="code"
                name="code"
                value="{{ old('code', $product->code) }}"
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
                value="{{ old('name', $product->name) }}"
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
                        {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}
                    >
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="supplier_id">Supplier</label>
            <select id="supplier_id" name="supplier_id">
                <option value="">-- Tanpa Supplier --</option>

                @foreach ($suppliers as $supplier)
                    <option
                        value="{{ $supplier->id }}"
                        {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}
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
                value="{{ old('unit', $product->unit) }}"
                maxlength="30"
                required
            >
            @error('unit')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label>Stok Saat Ini</label>
            <p>{{ number_format($product->stock, 0, ',', '.') }} {{ $product->unit }}. Gunakan menu Stok Masuk, Stok Keluar, atau Penyesuaian Stok untuk mengubah saldo.</p>
        </div>

        <div class="form-group">
            <label for="minimum_stock">Stok Minimum *</label>
            <input
                type="number"
                id="minimum_stock"
                name="minimum_stock"
                value="{{ old('minimum_stock', $product->minimum_stock) }}"
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
                placeholder="Keterangan tambahan produk"
            >{{ old('description', $product->description) }}</textarea>
            @error('description')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="is_active">Status Produk *</label>
            <select id="is_active" name="is_active" required>
                <option value="1"
                    {{ (string) old('is_active', (int) $product->is_active) === '1' ? 'selected' : '' }}>
                    Aktif
                </option>
                <option value="0"
                    {{ (string) old('is_active', (int) $product->is_active) === '0' ? 'selected' : '' }}>
                    Tidak Aktif
                </option>
            </select>
            @error('is_active')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Simpan Perubahan
            </button>

            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection