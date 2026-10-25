@extends('layouts.app')

@section('title', 'Penyesuaian Stok')
@section('page-title', 'Penyesuaian Stok')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>Penyesuaian Stok</h1>
            <p>Masukkan hasil stok opname fisik. Sistem mencatat selisih dari stok saat ini.</p>
        </div>
        <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">Kembali</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa kembali data penyesuaian.</strong>
            <ul>
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="card form-panel">
        <form action="{{ route('stock-adjustments.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="product_id">Produk <span>*</span></label>
                <select class="form-control" name="product_id" id="product_id" required>
                    <option value="">Pilih produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                            {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->code }} — {{ $product->name }}
                            (stok sistem {{ number_format($product->stock, 0, ',', '.') }} {{ $product->unit }})
                        </option>
                    @endforeach
                </select>
                @error('product_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="form-group">
                <label for="actual_stock">Stok fisik hasil opname <span>*</span></label>
                <input class="form-control" type="number" name="actual_stock" id="actual_stock"
                       min="0" max="4294967295" value="{{ old('actual_stock') }}" required>
                @error('actual_stock') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="form-group">
                <label for="transaction_date">Tanggal opname <span>*</span></label>
                <input class="form-control" type="datetime-local" name="transaction_date"
                       id="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}"
                       required>
                @error('transaction_date') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="form-group">
                <label for="notes">Alasan / catatan penyesuaian <span>*</span></label>
                <textarea class="form-control" name="notes" id="notes" rows="3" required>{{ old('notes') }}</textarea>
                @error('notes') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit" {{ $products->isEmpty() ? 'disabled' : '' }}>
                    Simpan Penyesuaian
                </button>
                <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
