<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>BAST {{ $document['data']['reference'] }}</title>
<style>body{font:11pt/1.5 Arial,sans-serif;background:#eee;color:#111;margin:0}.paper{max-width:180mm;margin:24px auto;padding:18mm;background:white}header,h1,.number{text-align:center}h1{font-size:16pt}.actions{text-align:center;padding:12px}.actions button,.actions a{padding:8px;margin:4px}table{width:100%;border-collapse:collapse;margin:18px 0}th,td{text-align:left;border:1px solid #ccc;padding:8px}th{width:35%;background:#f4f4f4}.notes{white-space:pre-line}.signatures{display:flex;gap:25px;margin-top:25px;break-inside:avoid}.signatures>div{flex:1;text-align:center}.hint{font-size:9pt;color:#555}@media print{body{background:white}.actions{display:none}.paper{padding:0;margin:0;max-width:none}tr{break-inside:avoid}@page{size:A4;margin:20mm}}</style>
</head><body><div class="actions"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button><a href="{{ route('aset.show', $asset) }}">Kembali ke Aset</a></div>
@php($data = $document['data'])
<article class="paper"><header><strong>VILLA MERAH</strong></header><h1>BERITA ACARA SERAH TERIMA ASET</h1><p class="number">Nomor / Referensi: <strong>{{ $data['reference'] }}</strong></p>
@if (! $document['snapshot'])<p><strong>DRAF REKONSTRUKSI — DATA BARANG TERKINI</strong><br>Serah terima lama tidak memiliki snapshot. Nama petugas dan data barang dapat berubah sejak serah terima. Verifikasi kembali sebelum dokumen ditandatangani.</p>@endif
<p>Tanggal serah terima: {{ $data['date'] }}<br>Pihak yang menyerahkan: {{ $data['sender'] }}<br>Pihak yang menerima: {{ $data['recipient'] }}</p>
<p>Dokumen ini mencatat serah terima satu unit aset dengan rincian berikut:</p>
<table>@foreach (['asset_number' => 'Nomor inventaris', 'name' => 'Nama barang', 'category' => 'Kategori', 'serial' => 'Serial number', 'condition' => 'Kondisi', 'location' => 'Lokasi penempatan', 'room' => 'Ruangan'] as $key => $label)<tr><th>{{ $label }}</th><td>{{ $data[$key] ?: 'Tidak tercatat' }}</td></tr>@endforeach</table>
@if ($data['notes'])<p class="notes">Catatan: {{ $data['notes'] }}</p>@endif
<p>Pihak yang menerima menyatakan telah menerima aset sesuai rincian di atas. Pengesahan fisik dilakukan dengan tanda tangan kedua pihak.</p>
<div class="signatures"><div>Yang menyerahkan<br><br><br>____________________<br>{{ $data['sender'] }}</div><div>Yang menerima<br><br><br>____________________<br>{{ $data['recipient'] }}</div></div>
<p class="hint">Nama tercetak dan catatan aplikasi bukan tanda tangan digital.</p>
</article></body></html>
