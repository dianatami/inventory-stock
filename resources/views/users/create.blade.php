
@extends('layouts.app')

@section('title', 'Tambah Pengguna - OMPROF')
@section('page-title', 'Tambah Pengguna')

@section('content')
<div class="container">

    <div class="page-header">
        <div>
            <h1>Tambah Pengguna</h1>
            <p>Daftarkan akun baru untuk mengakses sistem inventori.</p>
        </div>

        <a href="{{ route('users.index') }}" class="btn btn-secondary">
            Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa kembali data yang dimasukkan.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf

            <div class="user-form-grid">

                <div class="form-group full-width">
                    <label for="name">Nama Lengkap *</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Masukkan nama lengkap"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="username">Username *</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="Contoh: staff01"
                        autocomplete="username"
                        required
                    >
                    <small>Gunakan huruf, angka, tanda hubung, atau garis bawah.</small>
                </div>

                <div class="form-group">
                    <label for="email">Email (Opsional)</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Kosongkan jika tidak ada"
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group full-width">
                    <label for="password">Password *</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                </div>

                <div class="form-group full-width">
                    <label for="role">Role *</label>
                    <select name="role" id="role" required>
                        <option value="">Pilih role pengguna</option>

                        @foreach ($roles as $role)
                            <option value="{{ $role }}"
                                {{ old('role') === $role ? 'selected' : '' }}>
                                {{ $role }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="form-actions">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    + Tambah Pengguna
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
