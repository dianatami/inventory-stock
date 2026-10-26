@extends('layouts.app')
@section('title', 'Distribusi Barang - TPN')
@section('page-title', 'Distribusi Barang')
@section('content')
<div class="container"><div class="page-header"><div><h1>Validasi Penerimaan</h1><p>Periksa jumlah barang yang dilaporkan outlet dan tambahkan hanya jumlah yang disetujui ke stok outlet.</p></div><a href="{{ route('distributions.index') }}" class="btn btn-primary">Lihat Distribusi</a></div>
    @if ($errors->any())<div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="page-header"><div><h2>Konfirmasi Barang Diterima Outlet</h2><p>Stok outlet bertambah setelah jumlah aktual dikonfirmasi oleh petugas gudang.</p></div></div>
    @forelse ($receipts as $receipt)
        <section class="card" style="margin-bottom:18px">
            <div class="page-header"><div><h2>{{ $receipt->order->order_number }} · {{ $receipt->outlet->name }}</h2><p>Dikonfirmasi {{ $receipt->submitted_at->format('d/m/Y H:i') }} · <span class="badge">{{ ucfirst($receipt->status) }}</span></p></div></div>
            @if ($receipt->notes)<p>{{ $receipt->notes }}</p>@endif
            @if ($receipt->status === 'submitted')
                <form method="POST" action="{{ route('warehouse.receipts.validate', $receipt) }}">@csrf
                    <div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Dikirim</th><th>Diterima</th><th>Rusak</th><th>Kurang</th><th>Layak tervalidasi</th></tr></thead><tbody>
                    @foreach ($receipt->items as $item)<tr><td>{{ $item->orderItem->product->name }}</td><td>{{ $item->orderItem->dispatched_quantity }}</td><td>{{ $item->received_quantity }}</td><td>{{ $item->damaged_quantity }}</td><td>{{ $item->short_quantity }}</td><td><input class="form-control" type="number" name="validated[{{ $item->id }}]" min="0" max="{{ $item->received_quantity - $item->damaged_quantity }}" value="{{ $item->received_quantity - $item->damaged_quantity }}" required></td></tr>@endforeach
                    </tbody></table></div>
                    <div class="form-group"><label>Catatan gudang (opsional)</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
                    <button class="btn btn-primary" onclick="return confirm('Konfirmasi distribusi dan tambahkan jumlah diterima ke stok outlet?')">Konfirmasi Distribusi</button>
                </form>
            @else
                <div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Diterima outlet</th><th>Rusak</th><th>Kurang</th><th>Layak tervalidasi</th></tr></thead><tbody>
                @foreach ($receipt->items as $item)<tr><td>{{ $item->orderItem->product->name }}</td><td>{{ $item->received_quantity }}</td><td>{{ $item->damaged_quantity }}</td><td>{{ $item->short_quantity }}</td><td>{{ $item->validated_quantity }}</td></tr>@endforeach
                </tbody></table></div>
            @endif
        </section>
    @empty<div class="card empty-state">Belum ada konfirmasi penerimaan dari outlet.</div>@endforelse
    {{ $receipts->links() }}
</div>
@endsection
