
@extends('layouts.app')

@section('title', 'Tambah Kategori - OMPROF Inventory')
@section('page-title', 'Tambah Kategori')

@section('content')
    <div class="section-panel form-panel">
        <h3>Form Tambah Kategori</h3>
        <p>Masukkan informasi kategori yang ingin ditambahkan.</p>

        @if ($errors->any())
            <div class="alert-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('categories.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name">Nama Kategori <span>*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    maxlength="255"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                >{{ old('description') }}</textarea>
            </div>

            <div class="form-actions">
                <a href="{{ route('categories.index') }}" class="btn-secondary">
                    Kembali
                </a>
                <button type="submit" class="btn-primary">
                    Simpan Kategori
                </button>
            </div>
        </form>
    </div>
@endsection
