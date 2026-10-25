@extends('layouts.outlet')
@section('title', 'Pemesanan Barang - TPN')
@section('page-title', 'Pemesanan Barang')
@section('content')
<div class="outlet-page-head"><div><h1>Pesanan Barang</h1><p>Pantau persetujuan, pengiriman, dan konfirmasi penerimaan.</p></div><a href="{{ route('outlet.orders.create') }}" class="btn">+ Buat Pesanan</a></div>
@forelse ($orders as $order)
<section class="outlet-panel">
    <div class="outlet-page-head"><div><h2 style="font-size:18px">{{ $order->order_number }}</h2><p>{{ $order->created_at->format('d/m/Y H:i') }} · <span class="outlet-badge">{{ str_replace('_', ' ', ucfirst($order->status)) }}</span></p></div></div>
    @if ($order->decision_notes)<p class="outlet-muted">Catatan gudang: {{ $order->decision_notes }}</p>@endif
    <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Produk</th><th>Diminta</th><th>Disetujui</th><th>Dikirim</th></tr></thead><tbody>
    @foreach ($order->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->requested_quantity }} {{ $item->product->unit }}</td><td>{{ $item->approved_quantity ?? '-' }}</td><td>{{ $item->dispatched_quantity ?? '-' }}</td></tr>@endforeach
    </tbody></table></div>
    @if ($order->status === 'dispatched' && !$order->receipt)
        <form class="outlet-form" method="POST" action="{{ route('outlet.orders.receipt', $order) }}" style="margin-top:16px">@csrf
            <h3>Konfirmasi Jumlah Diterima</h3>
            <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Produk</th><th>Dikirim</th><th>Benar-benar diterima</th></tr></thead><tbody>
            @foreach ($order->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->dispatched_quantity }} {{ $item->product->unit }}</td><td><input type="number" name="received[{{ $item->id }}]" min="0" max="{{ $item->dispatched_quantity }}" value="{{ $item->dispatched_quantity }}" required></td></tr>@endforeach
            </tbody></table></div>
            <div class="outlet-field"><label>Catatan penerimaan (opsional)</label><textarea name="notes" rows="2"></textarea></div>
            <button class="btn" type="submit">Kirim Konfirmasi</button>
        </form>
    @elseif ($order->receipt)
        <p class="outlet-muted" style="margin-top:12px">Konfirmasi penerimaan: <span class="outlet-badge">{{ ucfirst($order->receipt->status) }}</span>
        @if ($order->receipt->validated_at) · divalidasi {{ $order->receipt->validated_at->format('d/m/Y H:i') }}@endif</p>
    @endif
</section>
@empty<section class="outlet-panel outlet-empty">Belum ada pesanan. Gunakan tombol “Buat Pesanan” untuk memulai.</section>@endforelse
<div>{{ $orders->links() }}</div>
@endsection
