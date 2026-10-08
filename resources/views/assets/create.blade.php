@extends('layouts.app-shell')

@section('page-title', 'Tambah Aset')
@section('header-title', 'Tambah Aset')

@section('headerAction')
    <div class="flex flex-wrap items-center gap-2">
    @if (auth()->user()->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL]))
        <a href="{{ route('aset.import.index') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">Impor Excel</a>
    @endif
        <a href="{{ route('laporan.export.excel') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Ekspor Excel</a>
        <a href="{{ route('laporan.export') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-100">Ekspor CSV</a>
    </div>
@endsection

@section('content')
    @if ($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('aset.store') }}" class="space-y-6">
            @csrf
            <fieldset class="space-y-2 rounded-lg border border-sky-200 bg-sky-50 p-4"><legend class="font-semibold">Syarat sebelum pemberian nomor</legend>@foreach (['received_confirmed' => 'Barang sudah diterima.', 'inspection_confirmed' => 'Jumlah, kondisi, dan spesifikasi barang sudah diperiksa.', 'documents_confirmed' => 'Dokumen perolehan / penerimaan sudah diverifikasi.'] as $field => $label)<label class="flex items-start gap-2 text-sm"><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field)) class="mt-1"><span>{{ $label }}</span></label>@endforeach<p class="text-xs text-slate-600">Untuk pengadaan melalui PO, gunakan alur Penerimaan. Nomor diterbitkan saat pencatatan selesai; barang belum aktif sampai label, penempatan, dan serah terima selesai.</p></fieldset>
            <div class="grid gap-5 md:grid-cols-2">
                @include('assets.catalog-select')
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nomor Inventaris</label>
                    <input type="text" name="asset_number" readonly placeholder="Dibuat otomatis saat disimpan" class="w-full rounded border border-slate-300 bg-slate-50 px-3 py-2">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Kategori</label>
                    <select name="asset_category_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama Barang</label>
                    <input type="text" name="name" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Serial Number</label>
                    <input type="text" name="serial_number" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Lokasi</label>
                    <select name="location_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                        <option value="">Pilih lokasi</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Ruangan</label>
                    <select name="room_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <option value="">Tidak ada</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->location?->name ?? '-' }} - {{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Kondisi</label>
                    <input type="text" name="condition" value="baik" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Status Penggunaan</label>
                    <input type="text" name="usage_status" value="dipakai" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Harga Beli</label>
                    <input type="number" step="0.01" name="purchase_price" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal Perolehan</label>
                    <input type="date" name="acquired_at" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
                    <textarea name="description" rows="4" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded bg-emerald-600 px-5 py-2.5 font-medium text-white hover:bg-emerald-500">Simpan Aset</button>
            </div>
        </form>
    </div>
@endsection
