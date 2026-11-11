@extends('layouts.app')
@section('title', 'Rekap Order Outlet - MieTime Logistik')
@section('page-title', 'Rekap Order Outlet')
@section('content')
<div class="container">
    <div class="page-header"><div><h1>Rekap Order Outlet</h1></div></div>
    <p class="text-muted">Halaman ini untuk melihat riwayat pesanan seluruh outlet. Untuk menyetujui dan memproses pengiriman, gunakan menu Proses Pesanan Outlet.</p>
    <div class="card" style="margin-bottom:18px">
        <form method="GET" action="{{ route('orders.recap.index') }}" class="user-filter-grid">
            <div class="form-group"><label for="outlet_id">Outlet</label><select id="outlet_id" name="outlet_id"><option value="">Semua outlet</option>@foreach ($outlets as $outlet)<option value="{{ $outlet->id }}" @selected((string) request('outlet_id') === (string) $outlet->id)>{{ $outlet->name }}</option>@endforeach</select></div>
            <div class="form-group"><label for="start_date">Dari tanggal</label><input id="start_date" name="start_date" type="date" value="{{ request('start_date') }}"></div>
            <div class="form-group"><label for="end_date">Sampai tanggal</label><input id="end_date" name="end_date" type="date" value="{{ request('end_date') }}"></div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Terapkan Filter</button><a class="btn btn-secondary" href="{{ route('orders.recap.index') }}">Reset</a></div>
        </form>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Nomor pesanan</th><th>Outlet</th><th>Tanggal pemesanan</th><th>Daftar barang / jumlah</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><strong>{{ $order->order_number }}</strong></td>
                            <td>{{ $order->outlet->name }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>@foreach ($order->items as $item)<div>{{ $item->product->name }}: {{ number_format($item->requested_quantity, 0, ',', '.') }} {{ $item->product->unit }}</div>@endforeach</td>
                            <td><span class="badge">{{ str_replace('_', ' ', ucfirst($order->status)) }}</span></td>
                            <td><a class="section-link" href="{{ route('orders.recap.show', $order) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">Tidak ada pesanan pada filter yang dipilih.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    </div>
</div>
@endsection
