@extends('layouts.app')
@section('title', 'Tambah Outlet - TPN')
@section('page-title', 'Tambah Outlet')
@section('content')
<div class="container"><div class="page-header"><div><h1>Tambah Outlet</h1><p>Data outlet dapat dipilih saat Manager membuat akun User Outlet.</p></div><a class="btn btn-secondary" href="{{ route('outlets.index') }}">Kembali</a></div>
    @include('outlets._form', ['outlet' => null, 'action' => route('outlets.store'), 'method' => 'POST'])
</div>
@endsection
