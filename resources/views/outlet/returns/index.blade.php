@extends('layouts.outlet')
@section('title', 'Retur Barang - TPN')
@section('page-title', 'Retur Barang')
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
<div class="outlet-page-head"><div><h1>Retur Barang</h1><p>Ajukan retur atas barang diterima, lalu pantau persetujuan dan verifikasi gudang.</p></div><a href="{{ route('outlet.returns.create') }}" class="btn">+ Ajukan Retur</a></div>
@forelse ($returns as $return)
<section class="outlet-panel">
    <div class="outlet-page-head"><div><h2 style="font-size:18px">{{ $return->return_number }}</h2><p>{{ $return->created_at->format('d/m/Y H:i') }} · <span class="outlet-badge">{{ $returnStatusLabels[$return->status] ?? $return->status }}</span></p></div></div>
    <p><strong>Alasan:</strong> {{ $return->reason }}</p>
    @if ($return->decision_notes)<p class="outlet-muted">Catatan keputusan: {{ $return->decision_notes }}</p>@endif
    <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Produk</th><th>Diajukan</th><th>Kondisi</th><th>Catatan</th><th>Dikirim outlet</th><th>Layak diterima gudang</th></tr></thead><tbody>
    @foreach ($return->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->requested_quantity }} {{ $item->product->unit }}</td><td>{{ str_replace('_', ' ', ucfirst($item->condition)) }}</td><td>{{ $item->notes ?? '-' }}</td><td>{{ $item->shipped_quantity ?? '-' }}</td><td>{{ $item->verified_quantity ?? '-' }}</td></tr>@endforeach
    </tbody></table></div>
    @if ($return->status === 'approved')
        <form method="POST" action="{{ route('outlet.returns.dispatch', $return) }}" class="outlet-actions" style="margin-top:16px">
            @csrf
            <button type="submit" class="btn" data-confirm="Kirim barang retur? Stok outlet akan berkurang setelah tindakan ini.">Kirim Barang Retur</button>
        </form>
    @endif
    <details style="margin-top:14px">
        <summary>Riwayat status</summary>
        <ul>
            @foreach ($return->statusHistory->sortBy('changed_at') as $history)
                <li>{{ $history->changed_at->format('d/m/Y H:i') }} · {{ $history->from_status ? str_replace('_', ' ', ucfirst($history->from_status)).' → ' : '' }}{{ str_replace('_', ' ', ucfirst($history->to_status)) }} · {{ $history->user?->name ?? 'Sistem' }}</li>
            @endforeach
        </ul>
    </details>
</section>
@empty<section class="outlet-panel outlet-empty">Belum ada pengajuan retur.</section>@endforelse
<div>{{ $returns->links() }}</div>
@endsection
