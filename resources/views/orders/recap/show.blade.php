@extends('layouts.app')
@section('title', 'Detail Pesanan - MieTime Logistik')
@section('page-title', 'Detail Pesanan Outlet')
@section('content')
<div class="container">
    <div class="page-header"><div><h1>{{ $order->order_number }}</h1></div><a href="{{ route('orders.recap.index') }}" class="btn btn-secondary">Kembali</a></div>
    <section class="card">
        <p><strong>Outlet:</strong> {{ $order->outlet->name }}</p>
        <p><strong>Tanggal pemesanan:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
        <p><strong>Pemesan:</strong> {{ $order->user->name }}</p>
        <p><strong>Status:</strong> <span class="badge">{{ str_replace('_', ' ', ucfirst($order->status)) }}</span></p>
        @if ($order->notes)<p><strong>Keterangan:</strong> {{ $order->notes }}</p>@endif
        <div class="table-responsive"><table class="table">
            <thead><tr><th>Barang</th><th>Jumlah pesanan</th><th>Disetujui</th><th>Dikirim</th><th>Satuan</th></tr></thead>
            <tbody>@foreach ($order->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ number_format($item->requested_quantity, 0, ',', '.') }}</td><td>{{ $item->approved_quantity ?? '-' }}</td><td>{{ $item->dispatched_quantity ?? '-' }}</td><td>{{ $item->product->unit }}</td></tr>@endforeach</tbody>
        </table></div>
        <h2>Riwayat status</h2>
        <ul>@foreach ($order->statusHistory->sortBy('changed_at') as $history)<li>{{ $history->changed_at->format('d/m/Y H:i') }} · {{ str_replace('_', ' ', ucfirst($history->to_status)) }} · {{ $history->user?->name ?? 'Sistem' }}@if ($history->notes) · {{ $history->notes }}@endif</li>@endforeach</ul>
    </section>
</div>
@endsection
