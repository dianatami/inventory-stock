@extends('layouts.app')
@section('title', 'Distribusi Barang - TPN')
@section('page-title', 'Distribusi Barang')
@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>Distribusi Barang</h1>
            <p>Riwayat pengiriman pesanan dari gudang menuju outlet.</p>
        </div>
        <a href="{{ route('warehouse.orders.index', ['status' => 'approved']) }}" class="btn btn-primary">Pesanan siap dikirim</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No. distribusi / pesanan</th>
                        <th>Tanggal distribusi</th>
                        <th>Outlet tujuan</th>
                        <th>Jenis produk</th>
                        <th>Total kuantitas</th>
                        <th>Petugas</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($distributions as $distribution)
                        @php
                            $sentItems = $distribution->items->filter(fn ($item) => (int) $item->dispatched_quantity > 0);
                            $statusLabels = [
                                'dispatched' => 'Dalam pengiriman',
                                'receipt_submitted' => 'Menunggu validasi penerimaan',
                                'completed' => 'Selesai',
                            ];
                        @endphp
                        <tr>
                            <td><strong>{{ $distribution->order_number }}</strong></td>
                            <td>{{ $distribution->dispatched_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>{{ $distribution->outlet->name }}</td>
                            <td>{{ $sentItems->count() }}</td>
                            <td>{{ number_format($sentItems->sum('dispatched_quantity'), 0, ',', '.') }}</td>
                            <td>{{ $distribution->dispatcher?->name ?? '-' }}</td>
                            <td><span class="badge">{{ $statusLabels[$distribution->status] ?? $distribution->status }}</span></td>
                            <td>
                                <details>
                                    <summary class="section-link">Lihat detail</summary>
                                    <div class="distribution-detail">
                                        <p><strong>Outlet:</strong> {{ $distribution->outlet->name }}</p>
                                        <p><strong>Nomor pesanan:</strong> {{ $distribution->order_number }}</p>
                                        @if ($distribution->notes)<p><strong>Catatan:</strong> {{ $distribution->notes }}</p>@endif
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead><tr><th>Produk</th><th>Diminta</th><th>Disetujui</th><th>Dikirim</th></tr></thead>
                                                <tbody>
                                                    @foreach ($distribution->items as $item)
                                                        <tr>
                                                            <td>{{ $item->product->name }}</td>
                                                            <td>{{ $item->requested_quantity }} {{ $item->product->unit }}</td>
                                                            <td>{{ $item->approved_quantity ?? '-' }}</td>
                                                            <td>{{ $item->dispatched_quantity ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <h3>Riwayat Status</h3>
                                        <ul class="distribution-history">
                                            @foreach ($distribution->statusHistory->sortBy('changed_at') as $history)
                                                <li>
                                                    {{ $history->changed_at->format('d/m/Y H:i') }} ·
                                                    {{ $history->from_status ? str_replace('_', ' ', ucfirst($history->from_status)).' → ' : '' }}{{ str_replace('_', ' ', ucfirst($history->to_status)) }} ·
                                                    {{ $history->user?->name ?? 'Sistem' }}
                                                    @if ($history->quantity !== null) · {{ number_format($history->quantity, 0, ',', '.') }} unit @endif
                                                    @if ($history->notes) · {{ $history->notes }} @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        <button type="button" class="btn btn-secondary" onclick="window.print()">Cetak surat jalan</button>
                                        @if ($distribution->status === 'receipt_submitted')
                                            <a class="btn btn-primary" href="{{ route('warehouse.receipts.index') }}">Validasi penerimaan</a>
                                        @endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">Belum ada distribusi yang dikirim.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $distributions->links() }}
    </div>
</div>
@endsection
