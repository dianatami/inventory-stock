```blade
@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')
<div class="page-header">
    <div>
        <h1>Edit Supplier</h1>
        <p>Perbarui informasi pemasok bahan baku restoran.</p>
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
    <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Nama Supplier <span>*</span></label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $supplier->name) }}"
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
                value="{{ old('company_name', $supplier->company_name) }}"
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
                value="{{ old('phone', $supplier->phone) }}"
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
                value="{{ old('whatsapp', $supplier->whatsapp) }}"
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
                value="{{ old('email', $supplier->email) }}"
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
            >{{ old('address', $supplier->address) }}</textarea>
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
            >{{ old('notes', $supplier->notes) }}</textarea>
            @error('notes')
                <small class="error-message">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Simpan Perubahan
            </button>

            <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
```