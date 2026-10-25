@extends('layouts.app')
@section('title', 'Distribusi Barang - TPN')
@section('page-title', 'Distribusi Barang')
@section('content')
<div class="container"><div class="page-header"><div><h1>Distribusi Barang</h1><p>Kelola pengiriman dari gudang dan konfirmasi jumlah yang benar-benar diterima outlet. Riwayat pengiriman tersinkron dengan Mutasi Stok.</p></div><a href="{{ route('warehouse.orders.index', ['status' => 'approved']) }}" class="btn btn-primary">Lihat Pesanan Disetujui</a></div>
    @if ($errors->any())<div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="card" style="margin-bottom:18px">
        <div class="page-header"><div><h2>Riwayat Pengiriman dari Gudang</h2><p>Setiap pengiriman dicatat sebagai transaksi keluar dan mutasi Gudang → Outlet.</p></div><a href="{{ route('stock-mutations.index') }}" class="section-link">Buka Mutasi Stok</a></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Tanggal kirim</th><th>Produk</th><th>Tujuan</th><th>Jumlah</th><th>Referensi</th><th>Petugas</th></tr></thead><tbody>
        @forelse ($distributions as $distribution)
            <tr><td>{{ $distribution->transaction_date->format('d/m/Y H:i') }}</td><td>{{ $distribution->product?->name ?? 'Produk tidak tersedia' }}</td><td>{{ $distribution->destination }}</td><td>{{ number_format($distribution->quantity, 0, ',', '.') }} {{ $distribution->product?->unit }}</td><td>{{ $distribution->reference_number ?: '-' }}</td><td>{{ $distribution->user?->name ?? '-' }}</td></tr>
        @empty<tr><td colspan="6" class="empty-state">Belum ada barang yang dikirim dari gudang.</td></tr>@endforelse
        </tbody></table></div>
        {{ $distributions->links() }}
    </section>
    <div class="page-header"><div><h2>Konfirmasi Barang Diterima Outlet</h2><p>Stok outlet bertambah setelah jumlah aktual dikonfirmasi oleh petugas gudang.</p></div></div>
    @forelse ($receipts as $receipt)
        <section class="card" style="margin-bottom:18px">
            <div class="page-header"><div><h2>{{ $receipt->order->order_number }} · {{ $receipt->outlet->name }}</h2><p>Dikonfirmasi {{ $receipt->submitted_at->format('d/m/Y H:i') }} · <span class="badge">{{ ucfirst($receipt->status) }}</span></p></div></div>
            @if ($receipt->notes)<p>{{ $receipt->notes }}</p>@endif
            @if ($receipt->status === 'submitted')
                <form method="POST" action="{{ route('warehouse.receipts.validate', $receipt) }}">@csrf
                    <div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Dikirim</th><th>Dilaporkan diterima</th><th>Jumlah tervalidasi</th></tr></thead><tbody>
                    @foreach ($receipt->items as $item)<tr><td>{{ $item->orderItem->product->name }}</td><td>{{ $item->orderItem->dispatched_quantity }}</td><td>{{ $item->received_quantity }}</td><td><input class="form-control" type="number" name="validated[{{ $item->id }}]" min="0" max="{{ $item->received_quantity }}" value="{{ $item->received_quantity }}" required></td></tr>@endforeach
                    </tbody></table></div>
                    <div class="form-group"><label>Catatan gudang (opsional)</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
                    <button class="btn btn-primary" onclick="return confirm('Konfirmasi distribusi dan tambahkan jumlah diterima ke stok outlet?')">Konfirmasi Distribusi</button>
                </form>
            @else
                <div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Dikonfirmasi outlet</th><th>Tervalidasi</th></tr></thead><tbody>
                @foreach ($receipt->items as $item)<tr><td>{{ $item->orderItem->product->name }}</td><td>{{ $item->received_quantity }}</td><td>{{ $item->validated_quantity }}</td></tr>@endforeach
                </tbody></table></div>
            @endif
        </section>
    @empty<div class="card empty-state">Belum ada konfirmasi penerimaan dari outlet.</div>@endforelse
    {{ $receipts->links() }}
</div>
@endsection
