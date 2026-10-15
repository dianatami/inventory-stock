@extends('layouts.app')

@section('title', 'Tambah Supplier')

@section('content')
<div class="page-header">
    <div>
        <h1>Tambah Supplier</h1>
        <p>Masukkan informasi pemasok bahan baku restoran.</p>
    </div>

    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">
        Kembali
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-container">
    <form action="{{ route('suppliers.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">Nama Supplier <span>*</span></label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name') }}"
                placeholder="Contoh: Budi Santoso"
                required
            >
            @error('name')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="company_name">Nama Perusahaan</label>
            <input
                type="text"
                id="company_name"
                name="company_name"
                value="{{ old('company_name') }}"
                placeholder="Contoh: PT Sumber Pangan"
            >
            @error('company_name')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="phone">Nomor Telepon</label>
            <input
                type="text"
                id="phone"
                name="phone"
                value="{{ old('phone') }}"
                placeholder="Contoh: 081234567890"
            >
            @error('phone')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="whatsapp">Nomor WhatsApp</label>
            <input
                type="text"
                id="whatsapp"
                name="whatsapp"
                value="{{ old('whatsapp') }}"
                placeholder="Contoh: 081234567890"
            >
            @error('whatsapp')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Contoh: supplier@email.com"
            >
            @error('email')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="address">Alamat</label>
            <textarea
                id="address"
                name="address"
                rows="3"
                placeholder="Masukkan alamat supplier"
            >{{ old('address') }}</textarea>
            @error('address')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="notes">Catatan</label>
            <textarea
                id="notes"
                name="notes"
                rows="3"
                placeholder="Catatan tambahan jika ada"
            >{{ old('notes') }}</textarea>
            @error('notes')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Simpan Supplier
            </button>

            <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
