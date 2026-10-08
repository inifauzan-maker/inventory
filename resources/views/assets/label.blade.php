<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Label {{ $asset->asset_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; color: #111; background: white; }
        .label { box-sizing: border-box; width: 90mm; min-height: 80mm; border: 2px solid #111; padding: 5mm; text-align: center; break-inside: avoid; }
        .brand { font-size: 16pt; font-weight: 700; }
        .heading { font-size: 12pt; margin-top: 1mm; }
        .number { font: 700 14pt monospace; margin: 4mm 0 1mm; overflow-wrap: anywhere; }
        .qr { display: block; width: 40mm; height: 40mm; margin: 0 auto 2mm; }
        .name { font-size: 12pt; font-weight: 700; overflow-wrap: anywhere; }
        button { padding: 8px 16px; margin-right: 12px; }
        @media screen and (max-width: 400px) { body { padding: 8px; } .label { max-width: 100%; } }
        @media print { .actions, .hint { display: none; } body { padding: 0; margin: 0; } @page { margin: 10mm; } }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Cetak Label</button>
        <a href="{{ route('aset.show', $asset) }}">Kembali ke Aset</a>
    </div>
    <p class="hint">QR Code berisi nomor inventaris saja. Cetak dengan skala 100%, lalu kembali ke detail aset untuk mencatat hasil cetak dan penempelannya.</p>
    <div class="label">
        <div class="brand">VILLA MERAH</div>
        <div class="heading">ASET INVENTARIS</div>
        <div class="number">{{ $asset->asset_number }}</div>
        <img class="qr" src="{{ $qrCode }}" alt="QR Code nomor inventaris {{ $asset->asset_number }}">
        <div class="name">{{ $asset->category?->code }} - {{ mb_strtoupper($asset->catalogItem?->name ?? $asset->name) }}</div>
    </div>
</body>
</html>
