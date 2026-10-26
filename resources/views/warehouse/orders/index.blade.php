@extends('layouts.app')
@section('title', 'Pesanan Outlet - TPN')
@section('page-title', 'Pesanan Outlet')
@section('content')
<div class="container">
    @php
        $orderStatusLabels = [
            'requested' => 'Menunggu pemeriksaan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'dispatched' => 'Dalam pengiriman',
            'receipt_submitted' => 'Menunggu validasi penerimaan',
            'completed' => 'Selesai',
        ];
    @endphp
    <div class="page-header"><div><h1>Pesanan Outlet</h1><p>Periksa permintaan, tetapkan jumlah disetujui, lalu kirim barang.</p></div></div>
    @if ($errors->any())<div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card" style="margin-bottom:18px"><form method="GET" action="{{ route('warehouse.orders.index') }}" class="user-filter-grid">
        <div class="form-group"><label>Status</label><select name="status"><option value="">Semua status</option>@foreach ($orderStatusLabels as $status => $label)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>@endforeach</select></div>
        <div class="form-actions"><button class="btn btn-primary">Filter</button></div>
    </form></div>
    @forelse ($orders as $order)
        <section class="card" style="margin-bottom:18px">
            <div class="page-header"><div><h2>{{ $order->order_number }} · {{ $order->outlet->name }}</h2><p>{{ $order->created_at->format('d/m/Y H:i') }} oleh {{ $order->user->name }} · <span class="badge">{{ $orderStatusLabels[$order->status] ?? $order->status }}</span></p></div></div>
            @if ($order->notes)<p>{{ $order->notes }}</p>@endif
            @if ($order->decision_notes)<p class="text-muted">Catatan: {{ $order->decision_notes }}</p>@endif
            <div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Stok gudang</th><th>Diminta</th><th>Disetujui</th><th>Dikirim</th></tr></thead><tbody>
                @foreach ($order->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->product->stock }} {{ $item->product->unit }}</td><td>{{ $item->requested_quantity }}</td><td>{{ $item->approved_quantity ?? '-' }}</td><td>{{ $item->dispatched_quantity ?? '-' }}</td></tr>@endforeach
            </tbody></table></div>
            @if ($order->status === 'requested')
                <form method="POST" action="{{ route('warehouse.orders.review', $order) }}" style="margin-top:16px">@csrf @method('PATCH')
                    <h3>Periksa dan tentukan jumlah</h3>
                    <div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Diminta</th><th>Jumlah disetujui</th></tr></thead><tbody>
                    @foreach ($order->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->requested_quantity }}</td><td><input class="form-control" type="number" name="approved[{{ $item->id }}]" min="0" max="{{ $item->requested_quantity }}" value="{{ $item->requested_quantity }}" required></td></tr>@endforeach
                    </tbody></table></div>
                    <div class="form-group"><label>Catatan keputusan</label><textarea name="decision_notes" rows="2" class="form-control"></textarea></div>
                    <div class="form-actions"><button name="decision" value="approve" class="btn btn-primary">Setujui Pesanan</button><button name="decision" value="reject" class="btn btn-secondary">Tolak Pesanan</button></div>
                </form>
            @elseif ($order->status === 'approved')
                <form method="POST" action="{{ route('warehouse.orders.dispatch', $order) }}" style="margin-top:16px">@csrf
                    <button class="btn btn-primary" onclick="return confirm('Kirim barang? Stok gudang akan berkurang dan Mutasi Stok Gudang → Outlet akan dicatat.')">Kirim Barang & Catat Mutasi</button>
                </form>
            @endif
        </section>
    @empty<div class="card empty-state">Belum ada pesanan outlet.</div>@endforelse
    {{ $orders->links() }}
</div>
@endsection
