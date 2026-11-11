@extends('layouts.app')
@section('title', 'Detail Permintaan Barang - MieTime Logistik')
@section('page-title', 'Detail Permintaan Barang')
@section('content')
@php
    $statusLabels = ['pending' => 'Menunggu diproses', 'processing' => 'Diproses', 'completed' => 'Selesai', 'rejected' => 'Ditolak'];
    $supplier = $logisticsRequest->product->supplier;
    $whatsappDigits = preg_replace('/\D+/', '', (string) $supplier?->whatsapp);
    if (str_starts_with($whatsappDigits, '00')) {
        $whatsappDigits = substr($whatsappDigits, 2);
    }
    if (str_starts_with($whatsappDigits, '0')) {
        $whatsappDigits = '62'.substr($whatsappDigits, 1);
    }
    $canOpenSupplierWhatsApp = strlen($whatsappDigits) >= 8 && strlen($whatsappDigits) <= 15;
    $supplierMessage = 'Halo '.$supplier?->name.', kami ingin menindaklanjuti kebutuhan '.$logisticsRequest->product->name
        .' sebanyak '.number_format($logisticsRequest->quantity, 0, ',', '.').' '.$logisticsRequest->unit
        .'. Stok gudang saat ini '.number_format($logisticsRequest->product->stock, 0, ',', '.')
        .' '.$logisticsRequest->unit.' (batas minimum '
        .number_format($logisticsRequest->product->minimum_stock, 0, ',', '.').' '.$logisticsRequest->unit
        .'). Nomor permintaan: '.$logisticsRequest->request_number
        .($logisticsRequest->reason ? '. Keterangan: '.$logisticsRequest->reason : '');
@endphp
<div class="container">
    <div class="page-header"><div><h1>{{ $logisticsRequest->request_number }}</h1></div><a href="{{ route('logistics-requests.index') }}" class="btn btn-secondary">Kembali</a></div>
    <section class="card">
        <div class="table-responsive">
            <table class="table">
                <tbody>
                    <tr><th>Tanggal permintaan</th><td>{{ $logisticsRequest->created_at->format('d/m/Y H:i') }}</td></tr>
                    <tr><th>Pemohon</th><td>{{ $logisticsRequest->requester->name }}</td></tr>
                    <tr><th>Nama barang</th><td>{{ $logisticsRequest->product->name }}</td></tr>
                    <tr><th>Stok gudang saat ini</th><td>{{ number_format($logisticsRequest->product->stock, 0, ',', '.') }} {{ $logisticsRequest->unit }} · batas minimum {{ number_format($logisticsRequest->product->minimum_stock, 0, ',', '.') }} {{ $logisticsRequest->unit }}</td></tr>
                    <tr><th>Jumlah barang</th><td>{{ number_format($logisticsRequest->quantity, 0, ',', '.') }} {{ $logisticsRequest->unit }}</td></tr>
                    <tr><th>Supplier</th><td>{{ $supplier?->name ?? 'Belum ditentukan pada data barang' }}@if ($supplier?->whatsapp) · WhatsApp {{ $supplier->whatsapp }}@endif</td></tr>
                    <tr><th>Keterangan / alasan</th><td>{{ $logisticsRequest->reason ?: '-' }}</td></tr>
                    <tr><th>Status</th><td><span class="badge">{{ $statusLabels[$logisticsRequest->status] ?? $logisticsRequest->status }}</span></td></tr>
                    <tr><th>Catatan Admin Prolog</th><td>{{ $logisticsRequest->admin_notes ?: '-' }}</td></tr>
                    @if ($logisticsRequest->processor)
                        <tr><th>Diproses oleh</th><td>{{ $logisticsRequest->processor->name }} · {{ $logisticsRequest->processed_at?->format('d/m/Y H:i') }}</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if (auth()->user()->role === 'Admin Prolog')
            @if ($supplier && $canOpenSupplierWhatsApp)
                <a class="btn btn-primary" href="https://wa.me/{{ $whatsappDigits }}?text={{ rawurlencode($supplierMessage) }}" target="_blank" rel="noopener noreferrer">
                    <svg class="interface-icon" aria-hidden="true"><use href="#icon-whatsapp"/></svg>
                    Hubungi Supplier via WhatsApp
                </a>
            @else
                <p class="text-muted">Nomor WhatsApp supplier belum tersedia atau belum valid. Lengkapi data supplier sebelum menghubungi.</p>
            @endif
        @endif
        @if (in_array(auth()->user()->role, ['Manager', 'Admin Prolog'], true) && in_array($logisticsRequest->status, ['pending', 'processing'], true))
            <form method="POST" action="{{ route('logistics-requests.status', $logisticsRequest) }}" style="margin-top:20px">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <label for="status">Perbarui status</label>
                    <select id="status" name="status" required>
                        <option value="processing" @selected($logisticsRequest->status === 'processing')>Diproses</option>
                        <option value="completed">Selesai</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                </div>
                <div class="form-group"><label for="admin_notes">Catatan pemrosesan</label><textarea id="admin_notes" name="admin_notes" rows="3" maxlength="5000">{{ old('admin_notes', $logisticsRequest->admin_notes) }}</textarea></div>
                <button class="btn btn-primary" type="submit">Simpan Status</button>
            </form>
        @endif
    </section>
</div>
@endsection
