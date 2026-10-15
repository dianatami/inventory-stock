
@extends('layouts.app')

@section('title', 'Input ' . ($type === 'in' ? 'Stok Masuk' : 'Stok Keluar') . ' - OMPROF')

@section('page-title', $type === 'in' ? 'Input Stok Masuk' : 'Input Stok Keluar')

@section('content')
<div class="container">

    {{-- HEADER HALAMAN --}}
    <div class="page-header">
        <div>
            <h1>{{ $type === 'in' ? 'Input Stok Masuk' : 'Input Stok Keluar' }}</h1>
            <p>
                {{ $type === 'in'
                    ? 'Catat penerimaan stok produk ke dalam inventori.'
                    : 'Catat pengeluaran stok produk dari inventori.' }}
            </p>
        </div>

        <a href="{{ $type === 'in' ? route('stock-in.index') : route('stock-out.index') }}"
           class="btn btn-secondary">
            Kembali
        </a>
    </div>

    {{-- PESAN ERROR --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan. Periksa data berikut:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- PESAN SUKSES --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- FORM TRANSAKSI --}}
    <div class="card">
        <form
            action="{{ $type === 'in' ? route('stock-in.store') : route('stock-out.store') }}"
            method="POST"
        >
            @csrf

            {{-- Jenis transaksi ditentukan otomatis dari halaman --}}
            <input type="hidden" name="type" value="{{ $type }}">

            {{-- PRODUK --}}
            <div class="form-group">
                <label for="product_id">Produk <span>*</span></label>

                <select name="product_id" id="product_id" required>
                    <option value="">-- Pilih Produk --</option>

                    @forelse ($products as $product)
                        <option
                            value="{{ $product->id }}"
                            {{ old('product_id') == $product->id ? 'selected' : '' }}
                        >
                            {{ $product->code }} - {{ $product->name }}
                            (Stok: {{ $product->stock }} {{ $product->unit }})
                        </option>
                    @empty
                        <option value="" disabled>
                            Tidak ada produk aktif
                        </option>
                    @endforelse
                </select>

                @error('product_id')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- JUMLAH --}}
            <div class="form-group">
                <label for="quantity">Jumlah {{ $type === 'in' ? 'Masuk' : 'Keluar' }} <span>*</span></label>

                <input
                    type="number"
                    name="quantity"
                    id="quantity"
                    min="1"
                    max="4294967295"
                    value="{{ old('quantity') }}"
                    placeholder="Masukkan jumlah produk"
                    required
                >

                @error('quantity')
                    <small class="text-danger">{{ $message }}</small>
                @enderror

                @if ($type === 'out')
                    <small>
                        Jumlah stok keluar tidak boleh melebihi stok yang tersedia.
                    </small>
                @endif
            </div>

            {{-- TANGGAL TRANSAKSI --}}
            <div class="form-group">
                <label for="transaction_date">Tanggal dan Waktu <span>*</span></label>

                <input
                    type="datetime-local"
                    name="transaction_date"
                    id="transaction_date"
                    value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}"
                    required
                >

                @error('transaction_date')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- NOMOR REFERENSI --}}
            <div class="form-group">
                <label for="reference_number">Nomor Referensi</label>

                <input
                    type="text"
                    name="reference_number"
                    id="reference_number"
                    value="{{ old('reference_number') }}"
                    maxlength="255"
                    placeholder="Contoh: INV-001 atau nomor nota"
                >

                @error('reference_number')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- CATATAN --}}
            <div class="form-group">
                <label for="notes">Catatan</label>

                <textarea
                    name="notes"
                    id="notes"
                    rows="4"
                    placeholder="Masukkan catatan jika diperlukan"
                >{{ old('notes') }}</textarea>

                @error('notes')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- TOMBOL --}}
            <div class="form-actions">
                <button
                    type="submit"
                    class="btn btn-primary"
                    {{ $products->isEmpty() ? 'disabled' : '' }}
                >
                    Simpan {{ $type === 'in' ? 'Stok Masuk' : 'Stok Keluar' }}
                </button>

                <a
                    href="{{ $type === 'in' ? route('stock-in.index') : route('stock-out.index') }}"
                    class="btn btn-secondary"
                >
                    Batal
                </a>
            </div>

        </form>
    </div>

</div>
@endsection
