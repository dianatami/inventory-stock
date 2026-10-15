
@extends('layouts.app')

@section('title', 'Edit Kategori - OMPROF Inventory')
@section('page-title', 'Edit Kategori')

@section('content')
    <div class="section-panel form-panel">
        <h3>Edit Kategori</h3>
        <p>Perbarui informasi kategori yang dipilih.</p>

        @if ($errors->any())
            <div class="alert-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Nama Kategori <span>*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $category->name) }}"
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
                >{{ old('description', $category->description) }}</textarea>
            </div>

            <div class="form-actions">
                <a href="{{ route('categories.index') }}" class="btn-secondary">
                    Kembali
                </a>
                <button type="submit" class="btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection
