<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan {{ $order->order_number }} - MieTime Logistik</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#f3f4f6;color:#241b18;font:14px/1.5 Arial,sans-serif}
        .actions{max-width:210mm;margin:20px auto;display:flex;justify-content:space-between}
        .actions a,.actions button{padding:10px 16px;border:0;border-radius:6px;background:#8f1d14;color:#fff;text-decoration:none;font:inherit;cursor:pointer}
        .actions a{background:#615b58}
        .paper{width:210mm;min-height:297mm;margin:0 auto 24px;padding:18mm 17mm;background:#fff}
        .heading{display:flex;align-items:center;gap:18px;padding-bottom:18px;border-bottom:3px solid #8f1d14}
        .heading img{width:80px;height:80px;object-fit:cover;border-radius:50%}
        .heading h1{margin:0;color:#8f1d14;font-size:26px;letter-spacing:1px}
        .heading p{margin:3px 0 0;color:#655b56}
        .document-title{margin:24px 0;text-align:center}
        .document-title h2{margin:0;font-size:22px;letter-spacing:2px;text-decoration:underline}
        .document-title p{margin:5px 0 0}
        .meta{display:grid;grid-template-columns:1fr 1fr;gap:8px 26px;margin:20px 0}
        .meta div{border-bottom:1px dotted #a59a94;padding:6px 0}
        .meta strong{display:inline-block;min-width:145px}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #756d68;padding:9px 10px;text-align:left;vertical-align:top}
        th{background:#f8f2ed;text-align:center}
        .center{text-align:center}
        .notes{min-height:56px;margin-top:18px;padding:10px;border:1px solid #9a918c}
        .signatures{display:grid;grid-template-columns:1fr 1fr;gap:36px;margin-top:38px;text-align:center}
        .signature-space{height:92px}
        .signature-line{width:75%;margin:0 auto;border-top:1px solid #6d625d;padding-top:6px}
        @page{size:A4 portrait;margin:0}
        @media print{body{background:#fff}.actions{display:none}.paper{width:auto;min-height:297mm;margin:0;padding:18mm 17mm;box-shadow:none}}
    </style>
</head>
<body>
    <div class="actions">
        <a href="{{ route('distributions.index') }}">Kembali ke Distribusi Barang</a>
        <button type="button" onclick="window.print()">Cetak Surat Jalan</button>
    </div>
    <main class="paper">
        <header class="heading">
            <img src="{{ asset('images/mietime-logo.png') }}" alt="Logo MieTime">
            <div><h1>MieTime Logistik</h1><p>Dokumen pengiriman barang gudang ke outlet</p></div>
        </header>
        <div class="document-title"><h2>SURAT JALAN</h2><p>Dokumen pengiriman barang</p></div>
        <section class="meta">
            <div><strong>Nomor surat jalan</strong>{{ $order->order_number }}</div>
            <div><strong>Tanggal pengiriman</strong>{{ $order->dispatched_at?->format('d/m/Y H:i') ?? '-' }}</div>
            <div><strong>Outlet tujuan</strong>{{ $order->outlet->name }}</div>
            <div><strong>Nama sopir</strong>{{ $order->driver_name ?: '-' }}</div>
        </section>
        <table>
            <thead><tr><th style="width:7%">No.</th><th>Nama barang</th><th style="width:18%">Jumlah</th><th style="width:16%">Satuan</th><th style="width:24%">Keterangan</th></tr></thead>
            <tbody>
                @foreach ($order->items->filter(fn ($item) => (int) $item->dispatched_quantity > 0) as $item)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $item->product->name }}<br><small>{{ $item->product->code }}</small></td>
                        <td class="center">{{ number_format($item->dispatched_quantity, 0, ',', '.') }}</td>
                        <td class="center">{{ $item->product->unit }}</td>
                        <td>{{ $order->notes ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="notes"><strong>Catatan pengiriman:</strong><br>{{ $order->notes ?: '-' }}</div>
        <div class="signatures">
            <div><strong>Pengirim</strong><div class="signature-space"></div><div class="signature-line">{{ $order->dispatcher?->name ?? 'Nama dan tanda tangan' }}</div></div>
            <div><strong>Penerima</strong><div class="signature-space"></div><div class="signature-line">Nama dan tanda tangan</div></div>
        </div>
    </main>
</body>
</html>
