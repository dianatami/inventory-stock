@extends('layouts.outlet')
@section('title', 'Retur Barang - TPN')
@section('page-title', 'Retur Barang')
@section('content')
<div class="outlet-page-head"><div><h1>Retur Barang</h1><p>Ajukan retur atas barang diterima, lalu pantau persetujuan dan verifikasi gudang.</p></div><a href="{{ route('outlet.returns.create') }}" class="btn">+ Ajukan Retur</a></div>
@forelse ($returns as $return)
<section class="outlet-panel">
    <div class="outlet-page-head"><div><h2 style="font-size:18px">{{ $return->return_number }}</h2><p>{{ $return->created_at->format('d/m/Y H:i') }} · <span class="outlet-badge">{{ ucfirst($return->status) }}</span></p></div></div>
    <p><strong>Alasan:</strong> {{ $return->reason }}</p>
    @if ($return->decision_notes)<p class="outlet-muted">Catatan keputusan: {{ $return->decision_notes }}</p>@endif
    <div class="outlet-table-wrap"><table class="outlet-table"><thead><tr><th>Produk</th><th>Diajukan</th><th>Diverifikasi gudang</th></tr></thead><tbody>
    @foreach ($return->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->requested_quantity }} {{ $item->product->unit }}</td><td>{{ $item->verified_quantity ?? '-' }}</td></tr>@endforeach
    </tbody></table></div>
</section>
@empty<section class="outlet-panel outlet-empty">Belum ada pengajuan retur.</section>@endforelse
<div>{{ $returns->links() }}</div>
@endsection
