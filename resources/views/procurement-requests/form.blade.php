@extends('layouts.app-shell')
@section('page-title', $proposal->exists ? 'Ubah Draf Pengajuan' : 'Buat Surat Pengajuan')
@section('header-title', $proposal->exists ? 'Ubah Draf Pengajuan' : 'Buat Surat Pengajuan')
@section('content')
    @if ($errors->any())<div role="alert" class="mb-5 rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $proposal->exists ? route('pengajuan.update', $proposal) : route('pengajuan.store') }}" class="space-y-5">
        @csrf
        @if ($proposal->exists) @method('PUT') @endif
        <input type="hidden" name="submission_key" value="{{ old('submission_key', $submissionKey) }}">
        <input type="hidden" name="version" value="{{ old('version', $proposal->version ?? 1) }}">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="mb-4 text-sm text-slate-600">Pengaju: {{ auth()->user()->name }}. Nomor surat dibuat otomatis. Simpan draf untuk memeriksa surat sebelum dikirim ke Keuangan.</p>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach (['division' => 'Divisi / Unit Kerja', 'subject' => 'Perihal', 'request_date' => 'Tanggal Pengajuan'] as $field => $label)
                    <div><label for="{{ $field }}" class="mb-1 block text-sm font-medium">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'request_date' ? 'date' : 'text' }}" required value="{{ old($field, $proposal->$field instanceof \Carbon\CarbonInterface ? $proposal->$field->format('Y-m-d') : ($proposal->$field ?? ($field === 'request_date' ? now()->format('Y-m-d') : ($field === 'subject' ? 'Pengajuan Barang Inventaris' : '')))) }}" class="w-full rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-sky-500">@error($field)<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
                @endforeach
                <div><label for="selection_mode" class="mb-1 block text-sm font-medium">Jenis Daftar Barang</label><select id="selection_mode" name="selection_mode" class="w-full rounded-lg border border-slate-300 p-2"><option value="all" @selected(old('selection_mode', $proposal->selection_mode) === 'all')>Semua barang dibutuhkan</option><option value="alternatives" @selected(old('selection_mode', $proposal->selection_mode) === 'alternatives')>Alternatif (pilih satu barang)</option></select><p class="mt-1 text-xs text-slate-600">Harga alternatif tidak dijumlahkan. Keuangan menentukan rekomendasi.</p></div>
                <div class="md:col-span-2"><label for="purpose" class="mb-1 block text-sm font-medium">Alasan dan Tujuan Pengajuan</label><textarea id="purpose" name="purpose" rows="5" required maxlength="5000" class="w-full rounded-lg border border-slate-300 p-2">{{ old('purpose', $proposal->purpose) }}</textarea>@error('purpose')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div class="md:col-span-2"><label for="notes" class="mb-1 block text-sm font-medium">Keterangan Tambahan</label><textarea id="notes" name="notes" rows="3" maxlength="5000" class="w-full rounded-lg border border-slate-300 p-2">{{ old('notes', $proposal->notes) }}</textarea></div>
            </div>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="font-semibold">Daftar Barang / Alternatif</h3><p class="mt-1 text-sm text-slate-600">Harga kosong berarti belum diketahui. Isi angka tanpa pemisah ribuan. Maksimal 20 barang.</p>
            <div id="request-items" class="mt-4 space-y-4">
                @foreach (old('items', $proposal->exists ? $proposal->items->toArray() : [['name' => '', 'category' => '', 'quantity' => 1, 'unit' => 'Unit', 'estimated_unit_price' => '', 'specification' => '']]) as $index => $item)
                    @include('procurement-requests.item-form', ['index' => $index, 'item' => $item])
                @endforeach
            </div>
            <button id="add-item" type="button" class="mt-4 rounded-lg border border-sky-600 px-4 py-2 text-sm text-sky-700">Tambah Barang</button>
            <template id="item-template">@include('procurement-requests.item-form', ['index' => '__INDEX__', 'item' => ['quantity' => 1, 'unit' => 'Unit']])</template>
        </section>
        <div class="flex flex-wrap gap-3"><button class="rounded-lg bg-emerald-600 px-5 py-2.5 font-medium text-white">Simpan Draf</button><a href="{{ route('pengajuan.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5">Kembali</a></div>
    </form>
    <script>
        const itemContainer = document.getElementById('request-items');
        let nextIndex = Math.max(-1, ...Array.from(itemContainer.children, item => Number(item.dataset.index)).filter(Number.isFinite)) + 1;
        document.getElementById('add-item').addEventListener('click', () => {
            if (itemContainer.children.length >= 20) return;
            const html = document.getElementById('item-template').innerHTML.replaceAll('__INDEX__', String(nextIndex++));
            itemContainer.insertAdjacentHTML('beforeend', html);
        });
        itemContainer.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-item]');
            if (button && itemContainer.children.length > 1) button.closest('[data-item]').remove();
        });
    </script>
@endsection

