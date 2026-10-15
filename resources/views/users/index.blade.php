
@extends('layouts.app')

@section('title', 'Manajemen Pengguna - OMPROF')
@section('page-title', 'Manajemen Pengguna')

@section('content')
<div class="container">

    {{-- HEADER --}}
    <div class="page-header">
        <div>
            <h1>Manajemen Pengguna</h1>
            <p>Kelola akun, hak akses, dan status pengguna sistem.</p>
        </div>

        <a href="{{ route('users.create') }}" class="btn btn-primary">
            + Tambah User
        </a>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- PENCARIAN DAN FILTER --}}
    <div class="card">
        <form action="{{ route('users.index') }}" method="GET">
            <div class="user-filter-grid">

                <div class="form-group">
                    <label for="search">Cari Pengguna</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Nama, username, atau email..."
                    >
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select name="role" id="role">
                        <option value="">Semua Role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}"
                                {{ request('role') === $role ? 'selected' : '' }}>
                                {{ $role }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">Semua Status</option>
                        <option value="active"
                            {{ request('status') === 'active' ? 'selected' : '' }}>
                            Aktif
                        </option>
                        <option value="inactive"
                            {{ request('status') === 'inactive' ? 'selected' : '' }}>
                            Nonaktif
                        </option>
                    </select>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Cari
                </button>

                <a href="{{ route('users.index') }}"
                   class="btn btn-secondary">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- TABEL PENGGUNA --}}
    <div class="card">
        <div class="page-header">
            <div>
                <h2>Daftar Pengguna</h2>
                <p>Informasi akun yang terdaftar dalam sistem.</p>
            </div>

            <span class="badge">
                {{ $users->total() }} pengguna
            </span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="user-table-name">
                                    <div class="user-table-avatar">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>

                                    <div>
                                        <strong>{{ $user->name }}</strong>
                                        <small>
                                            {{ '@' . $user->username }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $user->email }}</td>

                            <td>
                                <span class="user-role-badge
                                    {{ $user->role === 'Manager' ? 'manager' : 'staff' }}">
                                    {{ $user->role }}
                                </span>
                            </td>

                            <td>
                                @if ($user->is_active)
                                    <span class="user-status-badge active">
                                        Aktif
                                    </span>
                                @else
                                    <span class="user-status-badge inactive">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="user-action-buttons">

                                    <a href="{{ route('users.edit', $user) }}"
                                       class="user-action-btn edit"
                                       title="Edit pengguna"
                                       aria-label="Edit pengguna">
                                        ✎
                                    </a>

                                    @if ($user->id !== auth()->id())
                                        <form
                                            action="{{ route('users.toggle-status', $user) }}"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin mengubah status pengguna ini?')">
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="user-action-btn status"
                                                title="{{ $user->is_active ? 'Nonaktifkan pengguna' : 'Aktifkan pengguna' }}"
                                                aria-label="Ubah status pengguna">
                                                {{ $user->is_active ? '⏻' : '✓' }}
                                            </button>
                                        </form>

                                        <form
                                            action="{{ route('users.destroy', $user) }}"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus akun {{ addslashes($user->name) }}? Tindakan ini tidak dapat dibatalkan.')">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="user-action-btn delete"
                                                title="Hapus pengguna"
                                                aria-label="Hapus pengguna">
                                                🗑
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">
                                Belum ada pengguna yang sesuai dengan pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">
            {{ $users->links() }}
        </div>
    </div>

</div>
@endsection
