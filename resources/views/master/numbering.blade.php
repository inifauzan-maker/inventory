@extends('layouts.app-shell')
@section('page-title', 'Master Nomor Inventaris')
@section('header-title', 'Master Nomor Inventaris')
@section('content')
    @if (session('success'))<div role="status" class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
    @if ($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="font-semibold">Format Penomoran Otomatis</h3>
        <p class="text-sm text-slate-600">Nomor unik dan permanen: nomor yang pernah diterbitkan tidak boleh digunakan kembali untuk aset berbeda. Format teks dapat dicetak pada label fisik serta dijadikan isi QR Code/barcode. Tahun dan nomor urut membantu pelacakan; nomor dapat dicari dari pencarian inventaris.</p>
        <p class="text-sm text-slate-600">Nomor dibuat ketika aset disimpan. Urutan berlanjut per tahun dan format, dengan minimal enam digit. Nomor yang sudah digunakan dilewati; aset lama tidak dinomori ulang.</p>
        <p class="rounded-lg bg-slate-50 p-4 font-mono">{{ $settings->prefix }}{{ $settings->include_item_code ? '-BRG-000001' : '' }}-{{ now()->format('Y') }}-000001</p>
        @if (auth()->user()->isSuperAdmin())
        <form method="POST" action="{{ route('master.nomor.store') }}" class="space-y-4">
            @csrf
            <div><label for="prefix" class="mb-1 block text-sm">Awalan Nomor</label><input id="prefix" name="prefix" required maxlength="20" value="{{ old('prefix', $settings->prefix) }}" class="rounded-lg border border-slate-300 p-2"></div>
            <div><label for="include-code" class="mb-1 block text-sm">Susunan Nomor</label><select id="include-code" name="include_item_code" class="rounded-lg border border-slate-300 p-2"><option value="0" @selected(! old('include_item_code', $settings->include_item_code))>Awalan-Tahun-Urutan</option><option value="1" @selected(old('include_item_code', $settings->include_item_code))>Awalan-KodeBarang-Tahun-Urutan</option></select><p class="mt-1 text-xs text-slate-600">Jika aset belum terhubung ke master barang, nomor menggunakan Awalan-Tahun-Urutan.</p></div>
            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-white">Simpan Format</button>
        </form>
        @endif
    </section>
@endsection

