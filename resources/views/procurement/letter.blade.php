<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Surat PO {{ $order->order_number }}</title>
<style>
body{font:11pt/1.5 Arial,sans-serif;color:#111;background:#eee;margin:0}.paper{max-width:180mm;margin:24px auto;background:white;padding:18mm}header,h1,.status{text-align:center}h1{font-size:17pt;margin:8px 0}table{border-collapse:collapse;width:100%;margin:16px 0;font-size:10pt}th,td{border:1px solid #bbb;padding:8px;text-align:left;vertical-align:top}th{background:#eee}.money{text-align:right;white-space:nowrap}.actions{text-align:center;padding:12px}.actions a,.actions button{margin:4px;padding:8px}.notes{white-space:pre-line}.signatures{margin-top:25px;break-inside:avoid}.hint{font-size:9pt;color:#555}@media print{body{background:white}.paper{max-width:none;padding:0;margin:0}.actions{display:none}thead{display:table-header-group}tr{break-inside:avoid}@page{size:A4;margin:20mm}}
</style>
</head>
<body>
<div class="actions"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button><a href="{{ route('pengadaan.show', $order) }}">Kembali ke PO</a></div>
<article class="paper">
<header><strong>VILLA MERAH</strong></header>
<h1>SURAT PESANAN PEMBELIAN</h1>
<p class="status"><strong>{{ $order->order_number }}</strong><br>
{{ $order->status === 'approved' ? 'DISETUJUI' : ($order->status === 'rejected' ? 'DITOLAK — BUKAN PESANAN AKTIF' : 'DRAF — BELUM DISETUJUI') }}</p>
<p>Tanggal: {{ $order->order_date->format('d/m/Y') }}<br>Kepada vendor: {{ $order->vendor_name }}</p>
@if ($order->procurementRequest)<p>Pengajuan sumber: {{ $order->procurementRequest->letter_number }} · Versi {{ $order->procurement_request_version }}</p>@endif
<p>Berikut rincian barang dalam PO ini:</p>
@php($total = '0.00')
<table>
<thead><tr><th>No.</th><th>Barang / Satuan</th><th>Jumlah</th><th>Harga satuan</th><th>Subtotal</th></tr></thead>
<tbody>
@foreach ($order->items as $item)
    @php($subtotal = bcmul((string) $item->unit_price, (string) $item->quantity, 2))
    @php($total = bcadd($total, $subtotal, 2))
    <tr><td>{{ $loop->iteration }}</td><td class="notes">{{ $item->item_name }}@if ($item->procurementRequestItem) / {{ $item->procurementRequestItem->unit }}
{{ $item->procurementRequestItem->specification }}@endif
{{ $item->notes }}</td><td>{{ $item->quantity }}</td><td class="money">Rp {{ number_format($item->unit_price, 2, ',', '.') }}</td><td class="money">Rp {{ number_format($subtotal, 2, ',', '.') }}</td></tr>
@endforeach
<tr><th colspan="4">TOTAL</th><th class="money">Rp {{ number_format($total, 2, ',', '.') }}</th></tr>
</tbody></table>
@if ($order->notes)<p class="notes">Catatan: {{ $order->notes }}</p>@endif
<div class="signatures"><p>Disiapkan oleh: ____________________</p><p>Disetujui oleh Direksi: ____________________</p><p>Nama dan tanggal tanda tangan: ____________________</p></div>
<p class="hint">Status berasal dari aplikasi. Ruang tanda tangan di atas belum merupakan tanda tangan digital.</p>
</article></body></html>
