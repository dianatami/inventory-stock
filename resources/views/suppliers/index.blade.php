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
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Supplier</th>
                <th>Nama Perusahaan</th>
                <th>Telepon</th>
                <th>Email</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    <td>{{ $suppliers->firstItem() + $loop->index }}</td>
                    <td>{{ $supplier->name }}</td>
                    <td>{{ $supplier->company_name ?? '-' }}</td>
                    <td>{{ $supplier->phone ?? '-' }}</td>
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
