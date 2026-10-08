<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Surat Pengajuan {{ $proposal->letter_number }}</title>
<style>body{font:12pt/1.5 Arial,sans-serif;color:#111;margin:0;background:#eee}.paper{max-width:180mm;margin:24px auto;padding:20mm;background:white}h1{font-size:14pt;text-align:center}header{text-align:center;border-bottom:2px solid #333;padding-bottom:12px}table{border-collapse:collapse;width:100%;font-size:10pt}th,td{border:1px solid #444;padding:6px;text-align:left}p{white-space:pre-line}.actions{text-align:center;padding:12px}.signatures{display:flex;gap:20px;margin-top:24px}.signatures>div{flex:1;font-size:10pt}a,button{padding:8px}section{margin-top:16px}.text-slate-500{color:#555}@media print{body{background:white}.paper{margin:0;padding:0;max-width:none}.actions{display:none}thead{display:table-header-group}tr{break-inside:avoid}.signatures{break-inside:avoid}@page{size:A4;margin:20mm}}</style></head>
<body><div class="actions"><button onclick="window.print()">Cetak / Simpan PDF</button><a href="{{ route('pengajuan.show', $proposal) }}">Kembali</a></div><article class="paper">
<header><strong>BIMBEL GAMBAR VILLA MERAH</strong><br>Jl. Anggrek no.49, Kota Bandung, Jawa Barat</header>
<h1>SURAT PENGAJUAN BARANG INVENTARIS</h1><p style="text-align:center">No. {{ $proposal->letter_number }}<br>{{ App\Models\ProcurementRequest::statusLabels()[$proposal->status] }} · Versi {{ $proposal->version }}</p>
<p>Tanggal: {{ $proposal->request_date->translatedFormat('d F Y') }}<br>Perihal: {{ $proposal->subject }}<br>Kepada Yth.: Direktur Utama melalui Bagian Keuangan</p>
<p>Dengan hormat,</p><p>Divisi / unit kerja: {{ $proposal->division }}.<br>{{ $proposal->purpose }}</p>
@include('procurement-requests.items', ['proposal' => $proposal])
@if ($proposal->notes)<p>Keterangan:<br>{{ $proposal->notes }}</p>@endif
<p>Demikian pengajuan ini kami sampaikan untuk menunjang kebutuhan {{ $proposal->division }}. Atas perhatian dan persetujuannya, kami ucapkan terima kasih.</p>
<div class="signatures"><div><strong>Yang Mengajukan</strong><p>{{ $proposal->requester_name }}</p></div>
@foreach (['finance' => 'Mengetahui — Keuangan', 'director' => 'Menyetujui — Direksi'] as $level => $label)
<div><strong>{{ $label }}</strong>@php($decision = $proposal->decisions->firstWhere('level', $level))<p>{{ $decision ? $decision->reviewer_name : 'Belum diperiksa' }}</p>@if ($decision)<p>{{ $decision->decision === 'approved' ? 'Disetujui' : 'Ditolak' }}<br>{{ $decision->decided_at->format('d/m/Y H:i') }}<br>{{ $decision->notes }}@if ($decision->selectedItem)<br>Rekomendasi: {{ $decision->selectedItem->name }}@endif</p>@endif</div>
@endforeach</div><p style="font-size:9pt;color:#555">Keputusan tercatat di aplikasi. Nama yang tercantum bukan tanda tangan digital.</p>
</article></body></html>

