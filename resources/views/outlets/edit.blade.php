@extends('layouts.app')
@section('title', 'Edit Outlet - TPN')
@section('page-title', 'Edit Outlet')
@section('content')
<div class="container"><div class="page-header"><div><h1>Edit Outlet</h1><p>Outlet nonaktif tidak dapat dipilih untuk akun baru.</p></div><a class="btn btn-secondary" href="{{ route('outlets.index') }}">Kembali</a></div>
    @include('outlets._form', ['outlet' => $outlet, 'action' => route('outlets.update', $outlet), 'method' => 'PUT'])
</div>
@endsection
