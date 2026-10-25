
@extends('layouts.app')

@section('title', 'Edit Pengguna - OMPROF')
@section('page-title', 'Edit Pengguna')

@section('content')
<div class="container">

    {{-- HEADER --}}
    <div class="page-header">
        <div>
            <h1>Edit Pengguna</h1>
            <p>Perbarui informasi akun dan hak akses pengguna.</p>
        </div>

        <a href="{{ route('users.index') }}" class="btn btn-secondary">
            Kembali
        </a>
    </div>

    {{-- PESAN VALIDASI --}}
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

    {{-- FORM EDIT --}}
    <div class="card">
        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="user-form-grid">

                <div class="form-group full-width">
                    <label for="name">Nama Lengkap *</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
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
                        value="{{ old('username', $user->username) }}"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                    >
                    <small>
                        Username harus unik dan tidak boleh mengandung spasi.
                    </small>
                </div>
                <div class="form-group">
                    <label for="email">Email (Opsional)</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        placeholder="Kosongkan jika tidak ada"
                        autocomplete="email"
                    >
                </div>

                @if (auth()->user()->role === 'Manager')
                    <div class="form-group full-width">
                        <label for="password">Password Baru</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Kosongkan jika tidak ingin mengubah password"
                            autocomplete="new-password"
                            minlength="8"
                        >
                        <small>
                            Minimal 8 karakter. Password lama tetap digunakan jika dikosongkan.
                        </small>
                    </div>
                @endif

                <div class="form-group full-width">
                    <label for="role">Role *</label>
                    <select name="role" id="role" required>
                        @foreach ($roles as $role)
                            <option
                                value="{{ $role }}"
                                {{ old('role', $user->role) === $role ? 'selected' : '' }}
                            >
                                {{ $role }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group full-width" id="outlet-field">
                    <label for="outlet_id">Outlet *</label>
                    <select name="outlet_id" id="outlet_id">
                        <option value="">Pilih outlet untuk akun ini</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}"
                                @selected(old('outlet_id', $user->outlet_id) == $outlet->id)>
                                {{ $outlet->name }} ({{ $outlet->code }})
                            </option>
                        @endforeach
                    </select>
                    <small>Setiap outlet hanya dapat terhubung ke satu akun User Outlet.</small>
                </div>

            </div>

            {{-- INFORMASI AKUN --}}
            <div class="user-edit-info">
                <div>
                    <span>Status akun</span>
                    <strong>
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </strong>
                </div>

                <div>
                    <span>Terdaftar sejak</span>
                    <strong>
                        {{ $user->created_at?->format('d M Y') ?? '-' }}
                    </strong>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</div>
<script>
    const roleField = document.getElementById('role');
    const outletField = document.getElementById('outlet-field');
    const outletSelect = document.getElementById('outlet_id');
    const updateOutletField = () => {
        const isOutletUser = roleField.value === 'User Outlet';
        outletField.hidden = !isOutletUser;
        outletSelect.required = isOutletUser;
        if (!isOutletUser) outletSelect.value = '';
    };
    roleField.addEventListener('change', updateOutletField);
    updateOutletField();
</script>
@endsection
