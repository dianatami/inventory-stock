@extends('layouts.app')
@section('title', 'Data Outlet - TPN')
@section('page-title', 'Data Outlet')
@section('content')
<div class="container">
    <div class="page-header"><div><h1>Data Outlet</h1><p>Kelola lokasi outlet dan status aksesnya.</p></div><a href="{{ route('outlets.create') }}" class="btn btn-primary">+ Tambah Outlet</a></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card"><div class="table-responsive"><table class="table">
        <thead><tr><th>Kode</th><th>Outlet</th><th>Alamat</th><th>Telepon</th><th>Akun Outlet</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse ($outlets as $outlet)
            <tr><td>{{ $outlet->code }}</td><td><strong>{{ $outlet->name }}</strong></td><td>{{ $outlet->address ?? '-' }}</td><td>{{ $outlet->phone ?? '-' }}</td><td>{{ $outlet->user?->username ?? 'Belum terhubung' }}</td><td>{{ $outlet->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                <td><div class="user-action-buttons"><a class="user-action-btn edit" href="{{ route('outlets.edit', $outlet) }}" aria-label="Edit outlet">✎</a>
                    <form method="POST" action="{{ route('outlets.destroy', $outlet) }}" onsubmit="return confirm('Hapus outlet ini? Outlet dengan riwayat tidak dapat dihapus.')">@csrf @method('DELETE')<button class="user-action-btn delete" type="submit" aria-label="Hapus outlet">×</button></form>
                </div></td></tr>
        @empty<tr><td colspan="7" class="empty-state">Belum ada data outlet.</td></tr>@endforelse
        </tbody></table></div><div style="margin-top:16px">{{ $outlets->links() }}</div></div>
</div>
@endsection
