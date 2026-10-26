@extends('layouts.app')
@section('title', 'Retur Outlet - TPN')
@section('page-title', 'Retur Outlet')
@section('content')
@php
    $returnStatusLabels = [
        'requested' => 'Menunggu pemeriksaan',
        'approved' => 'Disetujui, menunggu pengiriman',
        'rejected' => 'Ditolak',
        'dispatched' => 'Dalam pengiriman ke gudang',
        'verified' => 'Selesai',
    ];
@endphp
<div class="container"><div class="page-header"><div><h1>Pengajuan Retur Outlet</h1><p>Kepala Logistik menyetujui pengajuan; gudang mencatat stok kembali setelah verifikasi fisik.</p></div></div>
    @if ($errors->any())<div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @forelse ($returns as $return)
        <section class="card" style="margin-bottom:18px">
            <div class="page-header"><div><h2>{{ $return->return_number }} · {{ $return->outlet->name }}</h2><p>{{ $return->created_at->format('d/m/Y H:i') }} · <span class="badge">{{ $returnStatusLabels[$return->status] ?? $return->status }}</span></p></div></div>
            <p><strong>Alasan:</strong> {{ $return->reason }}</p>
            @if ($return->decision_notes)<p>{{ $return->decision_notes }}</p>@endif
            @if ($return->dispatched_at)<p><strong>Dikirim oleh:</strong> {{ $return->dispatcher?->name ?? '-' }} · {{ $return->dispatched_at->format('d/m/Y H:i') }}</p>@endif
            <div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Diajukan</th><th>Kondisi</th><th>Catatan</th><th>Dikirim outlet</th><th>Layak diterima gudang</th></tr></thead><tbody>
                @foreach ($return->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->requested_quantity }}</td><td>{{ str_replace('_', ' ', ucfirst($item->condition)) }}</td><td>{{ $item->notes ?? '-' }}</td><td>{{ $item->shipped_quantity ?? '-' }}</td><td>{{ $item->verified_quantity ?? '-' }}</td></tr>@endforeach
            </tbody></table></div>
            <details style="margin-top:14px"><summary>Riwayat status</summary><ul>
                @foreach ($return->statusHistory->sortBy('changed_at') as $history)
                    <li>{{ $history->changed_at->format('d/m/Y H:i') }} · {{ $history->from_status ? str_replace('_', ' ', ucfirst($history->from_status)).' → ' : '' }}{{ str_replace('_', ' ', ucfirst($history->to_status)) }} · {{ $history->user?->name ?? 'Sistem' }}</li>
                @endforeach
            </ul></details>
            @if ($return->status === 'requested' && in_array(auth()->user()->role, ['Manager', 'Kepala Logistik']))
                <form method="POST" action="{{ route('warehouse.returns.review', $return) }}" style="margin-top:16px">@csrf @method('PATCH')
                    <div class="form-group"><label>Catatan keputusan</label><textarea class="form-control" name="decision_notes" rows="2"></textarea></div>
                    <div class="form-actions"><button class="btn btn-primary" name="decision" value="approve">Setujui Retur</button><button class="btn btn-secondary" name="decision" value="reject">Tolak Retur</button></div>
                </form>
            @elseif ($return->status === 'dispatched' && in_array(auth()->user()->role, ['Manager', 'Admin Prolog']))
                <form method="POST" action="{{ route('warehouse.returns.verify', $return) }}" style="margin-top:16px">@csrf
                    <h3>Verifikasi barang layak masuk stok gudang</h3>
                    <p class="text-muted">Barang rusak atau tidak layak jual tidak dihitung sebagai stok tersedia.</p>
                    <div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Dikirim outlet</th><th>Jumlah layak diterima gudang</th></tr></thead><tbody>
                    @foreach ($return->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->shipped_quantity }}</td><td><input class="form-control" type="number" name="verified[{{ $item->id }}]" min="0" max="{{ $item->shipped_quantity }}" value="{{ $item->shipped_quantity }}" required></td></tr>@endforeach
                    </tbody></table></div>
                    <button class="btn btn-primary" onclick="return confirm('Verifikasi jumlah layak dan tambahkan ke stok gudang?')">Catat Hasil Verifikasi Gudang</button>
                </form>
            @endif
        </section>
    @empty<div class="card empty-state">Belum ada pengajuan retur.</div>@endforelse
    {{ $returns->links() }}
</div>
@endsection
