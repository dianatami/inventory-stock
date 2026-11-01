@extends('layouts.app')

@section('title', 'Data Supplier')

@section('content')
<div class="page-header">
    <div>
        <h1>Data Supplier</h1>
        <p>Kelola data pemasok bahan baku restoran.</p>
    </div>

    <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
        + Tambah Supplier
    </a>
</div>

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="table-container">
    <form action="{{ route('suppliers.index') }}" method="GET" class="filter-form">
        <div class="form-group">
            <label for="supplier-search">Cari Supplier</label>
            <input
                type="search"
                id="supplier-search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Nama, perusahaan, WhatsApp, atau email"
            >
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Cari</button>
            @if (request()->filled('search'))
                <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Supplier</th>
                <th>Nama Perusahaan</th>
                <th>No. WhatsApp</th>
                <th>Email</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    @php
                        $whatsappDigits = preg_replace('/\D+/', '', (string) $supplier->whatsapp);
                        if (str_starts_with($whatsappDigits, '00')) {
                            $whatsappDigits = substr($whatsappDigits, 2);
                        }
                        if (str_starts_with($whatsappDigits, '0')) {
                            $whatsappDigits = '62'.substr($whatsappDigits, 1);
                        }
                        $canOpenWhatsApp = strlen($whatsappDigits) >= 8 && strlen($whatsappDigits) <= 15;
                    @endphp
                    <td>{{ $suppliers->firstItem() + $loop->index }}</td>
                    <td>{{ $supplier->name }}</td>
                    <td>{{ $supplier->company_name ?? '-' }}</td>
                    <td>
                        @if ($supplier->whatsapp)
                            @if ($canOpenWhatsApp)
                                <a class="supplier-whatsapp-link"
                                   href="https://wa.me/{{ $whatsappDigits }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   aria-label="Chat WhatsApp dengan {{ $supplier->name }} di {{ $supplier->whatsapp }}">
                                    <svg class="interface-icon" aria-hidden="true"><use href="#icon-whatsapp"/></svg>
                                    {{ $supplier->whatsapp }}
                                </a>
                            @else
                                {{ $supplier->whatsapp }}
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $supplier->email ?? '-' }}</td>
                    <td>
                        <div class="action-buttons">
                            <a
                                href="{{ route('suppliers.edit', $supplier) }}"
                                class="btn btn-secondary"
                            >
                                Edit
                            </a>

                            <form
                                action="{{ route('suppliers.destroy', $supplier) }}"
                                method="POST"
                                onsubmit="return confirm('Yakin ingin menghapus supplier ini?')"
                            >
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-danger">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-state">
                        Belum ada data supplier.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-container">
    {{ $suppliers->links() }}
</div>
@endsection
