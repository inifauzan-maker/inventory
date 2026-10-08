@extends('layouts.app-shell')

@section('page-title', 'Impor Excel Inventaris')
@section('header-title', 'Impor Excel Inventaris')
@section('headerAction')
    <a href="{{ route('aset.import.example') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">Unduh Contoh Data</a>
    <a href="{{ route('aset.import.template') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-100">Unduh Template</a>
@endsection

@section('content')
    <div class="space-y-6">
        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ session('success') }} <a href="{{ route('inventaris.index') }}" class="underline">Lihat inventaris</a></div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                <p class="font-semibold">Impor belum dapat dilanjutkan.</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900">1. Unggah data awal</h3>
            <p class="mt-2 text-sm text-slate-600">Nomor inventaris kosong akan dibuat otomatis saat konfirmasi. Kode barang yang diisi harus dipetakan ke Master Barang; jika kosong, sistem mencari master dari nama dan kategori yang sama.</p>
            <p class="mt-2 text-sm text-slate-600">Contoh unduhan berisi tiga aset fiktif. Ganti data contoh dengan data sebenarnya dan petakan kategori, lokasi, serta ruangan ke master yang tersedia.</p>
            <p class="mt-2 text-sm text-slate-600">Gunakan template Daftar_Inventaris_Data_Awal.xlsx. Isi sheet Inventaris, satu baris per aset fisik. Sheet Panduan dan baris kosong diabaikan. Maksimal 5 MB dan 200 aset.</p>
            <p class="mt-2 text-sm text-slate-600">Nomor inventaris, kode barang, dan serial number sebaiknya berformat teks untuk menjaga nol awal. Tanggal: dd/mm/yyyy atau tanggal Excel. Harga kosong tetap belum diketahui; angka 0 tetap nol. Formula tidak diperbolehkan.</p>
            <form method="POST" action="{{ route('aset.import.preview') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="grow">
                    <label for="import-file" class="mb-1 block text-sm font-medium">File Excel (.xlsx)</label>
                    <input id="import-file" type="file" name="file" accept=".xlsx" required class="w-full rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-sky-500" aria-describedby="file-error">
                    @error('file') <p id="file-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <button class="rounded-lg bg-sky-600 px-5 py-2.5 font-medium text-white hover:bg-sky-500">Tampilkan Preview</button>
            </form>
        </section>
        @if ($preview)
            <form method="POST" action="{{ route('aset.import.confirm') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">2. Periksa preview dan pemetaan</h3>
                    <p class="mt-2 break-all text-sm text-slate-600">{{ $filename }} · {{ count($preview['rows']) }} baris · {{ $preview['invalid'] }} baris bermasalah · {{ $preview['unmapped'] }} baris perlu pemetaan. Preview berlaku 30 menit sejak unggahan.</p>
                    <p class="mt-2 text-sm text-slate-600">Pilih master tujuan untuk setiap nilai Excel. Master yang belum tersedia dapat ditambahkan melalui menu Master Data, lalu pilih Perbarui Preview. Setelah mengubah lokasi, perbarui preview agar pilihan ruangan mengikuti lokasi tersebut.</p>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach ($preview['groups'] as $group)
                            <div>
                                <label for="mapping-{{ $group['key'] }}" class="mb-1 block text-sm font-medium">{{ $group['label'] }}: {{ $group['value'] }}{{ $group['location'] ? ' — '.$group['location'] : '' }}</label>
                                <select id="mapping-{{ $group['key'] }}" name="mapping[{{ $group['key'] }}]" class="w-full rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-sky-500">
                                    <option value="">Pilih master tujuan</option>
                                    @foreach ($group['options'] as $option)
                                        <option value="{{ $option->id }}" @selected((string) $group['selected'] === (string) $option->id)>{{ $option->code }} — {{ $option->name }}</option>
                                    @endforeach
                                </select>
                                @if (! $group['selected'])
                                    <p class="mt-1 text-sm text-amber-800">Belum dipetakan. Pilih master yang tersedia.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" formaction="{{ route('aset.import.mapping') }}" class="mt-4 rounded-lg border border-sky-600 px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50">Perbarui Preview</button>
                </section>
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50"><tr>
                                <th class="p-3">Baris</th><th class="p-3">Nomor / Barang</th><th class="p-3">Penempatan</th><th class="p-3">Kondisi / Status</th><th class="p-3">Perolehan</th><th class="p-3">Validasi</th>
                            </tr></thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($preview['rows'] as $row)
                                    <tr class="align-top">
                                        <td class="p-3">{{ $row['number'] }}</td>
                                        <td class="min-w-48 p-3"><p class="font-medium">{{ $row['data']['asset_number'] ?: 'Otomatis saat impor' }}</p><p>{{ $row['data']['name'] }}</p><p class="text-slate-500">SN: {{ $row['data']['serial_number'] ?? '-' }}</p>
                                            <details class="mt-2"><summary class="cursor-pointer text-sky-700">Semua kolom sumber</summary><dl class="mt-2 space-y-1">@foreach ($row['values'] as $label => $value)<div><dt class="font-medium">{{ $label }}</dt><dd class="break-all">{{ $value === '' ? '-' : $value }}</dd></div>@endforeach</dl></details>
                                        </td>
                                        <td class="min-w-40 p-3">{{ $row['values']['Cabang / Lokasi'] }}<br>{{ $row['values']['Ruangan'] ?: 'Tanpa ruangan' }}</td>
                                        <td class="min-w-40 p-3">{{ $row['data']['condition'] }}<br>{{ $row['data']['usage_status'] }}</td>
                                        <td class="min-w-40 p-3">{{ $row['data']['acquired_at'] && preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['data']['acquired_at']) ? implode('/', array_reverse(explode('-', $row['data']['acquired_at']))) : ($row['data']['acquired_at'] ?? 'Tanggal belum tersedia') }}<br>{{ $row['data']['purchase_price'] === null ? 'Harga belum diketahui' : (is_numeric($row['data']['purchase_price']) ? 'Rp '.number_format((float) $row['data']['purchase_price'], 2, ',', '.') : $row['data']['purchase_price']) }}</td>
                                        <td class="min-w-64 p-3">
                                            @foreach ($row['errors'] as $message)<p class="text-red-700">{{ $message }}</p>@endforeach
                                            @foreach ($row['warnings'] as $message)<p class="text-amber-800">{{ $message }}</p>@endforeach
                                            @if ($row['errors'] === [] && $row['warnings'] === [])<span class="font-medium text-emerald-700">Siap diimpor</span>@endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold">3. Konfirmasi impor</h3>
                    <p class="mt-2 text-sm text-slate-600">Seluruh baris harus valid dan dipetakan sebelum disimpan. Jika satu baris gagal, seluruh impor dibatalkan. Aset lama tidak ditimpa. Kolom tambahan (kode barang, merk, unit kerja, PIC, dan lainnya) disimpan dalam deskripsi aset. Tautan dokumen hanya disimpan sebagai teks.</p>
                    <label class="mt-4 flex items-start gap-2"><input type="checkbox" name="confirmed" value="1" class="mt-1"><span class="text-sm">Seluruh barang sudah diterima, diperiksa, dan dokumennya diverifikasi. Saya juga telah memeriksa seluruh baris dan pemetaan master. Setelah pencatatan, label, penempatan, dan serah terima akan dilanjutkan dari detail setiap aset.</span></label>
                    <button type="submit" @disabled($preview['invalid'] > 0 || $preview['unmapped'] > 0) class="mt-4 rounded-lg bg-emerald-600 px-5 py-2.5 font-medium text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50">Impor {{ count($preview['rows']) }} Aset</button>
                </section>
            </form>
        @endif
    </div>
@endsection

