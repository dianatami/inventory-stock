@extends('layouts.app')
@section('title', 'Retur Outlet - TPN')
@section('page-title', 'Retur Outlet')
@section('content')
<div class="container"><div class="page-header"><div><h1>Pengajuan Retur Outlet</h1><p>Kepala Logistik menyetujui pengajuan; gudang mencatat stok kembali setelah verifikasi fisik.</p></div></div>
    @if ($errors->any())<div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @forelse ($returns as $return)
        <section class="card" style="margin-bottom:18px">
            <div class="page-header"><div><h2>{{ $return->return_number }} · {{ $return->outlet->name }}</h2><p>{{ $return->created_at->format('d/m/Y H:i') }} · <span class="badge">{{ ucfirst($return->status) }}</span></p></div></div>
            <p><strong>Alasan:</strong> {{ $return->reason }}</p>
            @if ($return->decision_notes)<p>{{ $return->decision_notes }}</p>@endif
            <div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Diajukan</th><th>Diverifikasi gudang</th></tr></thead><tbody>
                @foreach ($return->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->requested_quantity }}</td><td>{{ $item->verified_quantity ?? '-' }}</td></tr>@endforeach
            </tbody></table></div>
            @if ($return->status === 'requested' && in_array(auth()->user()->role, ['Manager', 'Kepala Logistik']))
                <form method="POST" action="{{ route('warehouse.returns.review', $return) }}" style="margin-top:16px">@csrf @method('PATCH')
                    <div class="form-group"><label>Catatan keputusan</label><textarea class="form-control" name="decision_notes" rows="2"></textarea></div>
                    <div class="form-actions"><button class="btn btn-primary" name="decision" value="approve">Setujui Retur</button><button class="btn btn-secondary" name="decision" value="reject">Tolak Retur</button></div>
                </form>
            @elseif ($return->status === 'approved' && in_array(auth()->user()->role, ['Manager', 'Admin Prolog']))
                <form method="POST" action="{{ route('warehouse.returns.verify', $return) }}" style="margin-top:16px">@csrf
                    <h3>Verifikasi fisik barang yang kembali</h3>
                    <div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Diajukan</th><th>Benar-benar diterima gudang</th></tr></thead><tbody>
                    @foreach ($return->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->requested_quantity }}</td><td><input class="form-control" type="number" name="verified[{{ $item->id }}]" min="0" max="{{ $item->requested_quantity }}" value="{{ $item->requested_quantity }}" required></td></tr>@endforeach
                    </tbody></table></div>
                    <button class="btn btn-primary" onclick="return confirm('Verifikasi retur dan perbarui stok outlet serta gudang?')">Catat Barang Diterima Gudang</button>
                </form>
            @endif
        </section>
    @empty<div class="card empty-state">Belum ada pengajuan retur.</div>@endforelse
    {{ $returns->links() }}
</div>
@endsection
