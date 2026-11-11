@extends('layouts.app')
@section('title', 'Ajukan Permintaan Barang - MieTime Logistik')
@section('page-title', 'Permintaan Barang')
@section('content')
<div class="container">
    <div class="page-header"><div><h1>Ajukan Permintaan Barang</h1></div><a href="{{ route('logistics-requests.index') }}" class="btn btn-secondary">Kembali</a></div>
    <div class="alert alert-info">Permintaan ini khusus untuk barang gudang yang stoknya sudah mencapai atau berada di bawah batas minimum. Admin Prolog akan menindaklanjuti kebutuhan ini dengan menghubungi supplier melalui WhatsApp.</div>
    @if ($errors->any())
        <div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div class="card">
        <form method="POST" action="{{ route('logistics-requests.store') }}">
            @csrf
            <div class="form-group">
                <label for="product_id">Barang gudang menipis <span>*</span></label>
                <select id="product_id" name="product_id" required>
                    <option value="">Pilih barang yang perlu diisi ulang</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                            {{ $product->name }} · Stok {{ number_format($product->stock, 0, ',', '.') }} / minimum {{ number_format($product->minimum_stock, 0, ',', '.') }} {{ $product->unit }} · Supplier: {{ $product->supplier?->name ?? 'belum diatur' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="quantity">Jumlah yang dibutuhkan <span>*</span></label>
                <input id="quantity" name="quantity" type="number" min="1" max="4294967295" value="{{ old('quantity') }}" required>
            </div>
            <div class="form-group">
                <label for="reason">Keterangan / alasan <span>*</span></label>
                <textarea id="reason" name="reason" rows="4" maxlength="5000" required>{{ old('reason') }}</textarea>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit" @disabled($products->isEmpty())>Kirim Permintaan</button>
                <a href="{{ route('logistics-requests.index') }}" class="btn btn-secondary">Batal</a>
            </div>
            @if ($products->isEmpty())<p class="text-muted">Saat ini tidak ada barang aktif dengan stok di bawah atau sama dengan batas minimum.</p>@endif
        </form>
    </div>
</div>
@endsection
