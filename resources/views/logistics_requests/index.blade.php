@extends('layouts.app')
@section('title', 'Permintaan Barang - MieTime Logistik')
@section('page-title', 'Permintaan Barang')
@section('content')
<div class="container">
    <div class="page-header">
        <div><h1>Permintaan Barang</h1></div>
        @if (auth()->user()->role === 'Kepala Logistik')
            <a href="{{ route('logistics-requests.create') }}" class="btn btn-primary">+ Buat Permintaan</a>
        @endif
    </div>
    <div class="card" style="margin-bottom:18px">
        <form method="GET" action="{{ route('logistics-requests.index') }}" class="user-filter-grid">
            <div class="form-group">
                <label for="search">Cari permintaan / barang</label>
                <input id="search" name="search" value="{{ request('search') }}" maxlength="255">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach (['pending' => 'Menunggu diproses', 'processing' => 'Diproses', 'completed' => 'Selesai', 'rejected' => 'Ditolak'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Filter</button></div>
        </form>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Nomor permintaan</th><th>Tanggal</th><th>Pemohon</th><th>Barang</th><th>Jumlah</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr>
                            <td><strong>{{ $item->request_number }}</strong></td>
                            <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $item->requester->name }}</td>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit }}</td>
                            <td><span class="badge">{{ ['pending' => 'Menunggu diproses', 'processing' => 'Diproses', 'completed' => 'Selesai', 'rejected' => 'Ditolak'][$item->status] ?? $item->status }}</span></td>
                            <td><a class="section-link" href="{{ route('logistics-requests.show', $item) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">Belum ada permintaan barang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
</div>
@endsection
