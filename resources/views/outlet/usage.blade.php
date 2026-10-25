@extends('layouts.outlet')
@section('title', 'Barang Terpakai - TPN')
@section('page-title', 'Barang Terpakai')
@section('content')
<div class="outlet-page-head"><div><h1>Laporan Barang Terpakai</h1><p>Rumus: stok awal + barang diterima − stok akhir fisik.</p></div></div>
<section class="outlet-panel">
    <form method="GET" action="{{ route('outlet.usage.index') }}" class="outlet-form-grid" style="margin-bottom:18px">
        <div class="outlet-field"><label for="date">Tanggal opname</label><input type="date" name="date" id="date" value="{{ $date }}"></div>
        <div class="outlet-field"><label for="product_id">Produk</label><select name="product_id" id="product_id"><option value="">Semua produk</option>@foreach ($products as $product)<option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>@endforeach</select></div>
        <div class="outlet-field" style="align-self:end"><button type="submit" class="btn">Tampilkan</button></div>
    </form>
    <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Produk</th><th>Stok awal</th><th>Barang diterima</th><th>Stok akhir fisik</th><th>Barang terpakai</th><th>Selisih</th></tr></thead><tbody>
    @forelse ($usageItems as $item)
        <tr><td>{{ $item->product->name }}</td><td>{{ $item->opening_stock }} {{ $item->product->unit }}</td><td>{{ $item->received_quantity }} {{ $item->product->unit }}</td><td>{{ $item->closing_stock }} {{ $item->product->unit }}</td><td>{{ $item->used_quantity }} {{ $item->product->unit }}</td><td>@if ($item->discrepancy > 0)<span class="outlet-badge">Lebih fisik: {{ $item->discrepancy }} {{ $item->product->unit }}</span>@else-@endif</td></tr>
    @empty<tr><td colspan="6" class="outlet-empty">Belum ada data opname untuk filter yang dipilih.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
