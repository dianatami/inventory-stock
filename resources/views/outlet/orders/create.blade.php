@extends('layouts.outlet')
@section('title', 'Pesan Barang - TPN')
@section('page-title', 'Pesan Barang')
@section('content')
<div class="outlet-page-head"><div><h1>Ajukan Pesanan</h1><p>Masukkan jumlah untuk produk yang ingin diminta. Biarkan 0 untuk produk yang tidak dipesan.</p></div><a href="{{ route('outlet.orders.index') }}" class="btn" style="background:#6b625d">Kembali</a></div>
<section class="outlet-panel">
    <form class="outlet-form" method="POST" action="{{ route('outlet.orders.store') }}">@csrf
        <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Produk</th><th>Kode</th><th>Stok gudang</th><th>Satuan</th><th>Jumlah diminta</th></tr></thead><tbody>
        @forelse ($products as $product)
            <tr><td>{{ $product->name }}</td><td>{{ $product->code }}</td><td>{{ number_format($product->stock, 0, ',', '.') }}</td><td>{{ $product->unit }}</td><td><input type="hidden" name="items[{{ $product->id }}][product_id]" value="{{ $product->id }}"><input type="number" name="items[{{ $product->id }}][quantity]" min="0" max="{{ $product->stock }}" value="{{ old('items.'.$product->id.'.quantity', 0) }}" required></td></tr>
        @empty<tr><td colspan="5" class="outlet-empty">Belum ada produk aktif.</td></tr>@endforelse
        </tbody></table></div>
        <div class="outlet-field"><label for="notes">Catatan (opsional)</label><textarea id="notes" name="notes" rows="3">{{ old('notes') }}</textarea></div>
        <button class="btn" type="submit">Kirim Pesanan</button>
    </form>
</section>
@endsection
