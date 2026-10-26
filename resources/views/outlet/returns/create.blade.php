@extends('layouts.outlet')
@section('title', 'Ajukan Retur - TPN')
@section('page-title', 'Ajukan Retur')
@section('content')
<div class="outlet-page-head"><div><h1>Ajukan Retur Barang</h1><p>Jumlah retur tidak boleh melebihi barang yang telah divalidasi diterima maupun stok outlet saat ini.</p></div><a href="{{ route('outlet.returns.index') }}" class="btn" style="background:#6b625d">Kembali</a></div>
@forelse ($receipts as $receipt)
<section class="outlet-panel">
    <h2 style="font-size:18px">Penerimaan {{ $receipt->order->order_number }} · {{ $receipt->validated_at?->format('d/m/Y') }}</h2>
    <form class="outlet-form" method="POST" action="{{ route('outlet.returns.store') }}">@csrf
        <input type="hidden" name="receipt_id" value="{{ $receipt->id }}">
        <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Produk</th><th>Tervalidasi diterima</th><th>Stok outlet tersedia</th><th>Jumlah retur</th><th>Kondisi</th><th>Catatan produk</th></tr></thead><tbody>
        @foreach ($receipt->items as $item)
            @php($stock = $outlet->stocks->firstWhere('product_id', $item->orderItem->product_id))
            <tr>
                <td>{{ $item->orderItem->product->name }}</td>
                <td>{{ $item->validated_quantity }} {{ $item->orderItem->product->unit }}</td>
                <td>{{ $stock?->quantity ?? 0 }}</td>
                <td><input type="number" name="items[{{ $item->id }}]" min="0" max="{{ min($item->validated_quantity, $stock?->quantity ?? 0) }}" value="0" required></td>
                <td>
                    <select name="conditions[{{ $item->id }}]">
                        <option value="">Pilih kondisi</option>
                        <option value="damaged">Rusak</option>
                        <option value="expired">Kedaluwarsa</option>
                        <option value="wrong_item">Produk tidak sesuai</option>
                        <option value="packaging">Kemasan bermasalah</option>
                        <option value="other">Lainnya</option>
                    </select>
                </td>
                <td><input type="text" name="item_notes[{{ $item->id }}]" maxlength="1000" placeholder="Opsional"></td>
            </tr>
        @endforeach
        </tbody></table></div>
        <div class="outlet-field"><label>Alasan retur *</label><textarea name="reason" rows="3" required>{{ old('reason') }}</textarea></div>
        <button type="submit" class="btn">Kirim Pengajuan Retur</button>
    </form>
</section>
@empty<section class="outlet-panel outlet-empty">Belum ada penerimaan tervalidasi yang dapat diretur.</section>@endforelse
@endsection
