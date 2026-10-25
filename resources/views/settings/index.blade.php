@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan Profil')

@section('content')
<div class="container">
    <div class="page-header">
        <div><h1>Pengaturan Akun</h1><p>Perbarui informasi profil akun Anda.</p></div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa kembali data yang dimasukkan.</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="settings-grid">
        <section class="card form-panel">
            <h2>Profil</h2>
            <p>Informasi identitas akun yang digunakan pada sistem.</p>
            <form action="{{ route('settings.profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="name">Nama lengkap</label>
                    <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input class="form-control" type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" value="{{ old('email', $user->email) }}">
                </div>
                <div class="form-group"><label>Hak akses</label><input class="form-control" type="text" value="{{ $user->role }}" disabled></div>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Simpan Profil</button></div>
            </form>
        </section>

        @if ($user->role === 'Manager')
            <section class="card form-panel">
                <h2>Ubah Password</h2>
                <p>Gunakan minimal 8 karakter dan jangan bagikan password Anda.</p>
                <form action="{{ route('settings.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="current_password">Password saat ini</label>
                        <input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password baru</label>
                        <input class="form-control" type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi password baru</label>
                        <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="form-actions"><button class="btn btn-primary" type="submit">Ubah Password</button></div>
                </form>
            </section>
        @endif
    </div>

    @if ($user->role === 'Manager')
        <section class="card settings-user-note">
            <h2>Manajemen Akun Pengguna</h2>
            <p>Kelola akun pengguna dan hak akses melalui menu Manajemen Pengguna.</p>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Buka Manajemen Pengguna</a>
        </section>
    @endif
</div>
@endsection
