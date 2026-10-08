@extends('layouts.app-shell')
@section('page-title', 'Master Kode Barang')
@section('header-title', 'Master Kode Barang')
@section('content')
    @if (session('success'))<div role="status" class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
    @if ($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="grid gap-5 lg:grid-cols-2">
        <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-5">
            <p class="mb-4 text-sm text-slate-600">Satu kode barang mewakili jenis barang. Setiap unit aset memiliki nomor inventaris tersendiri.</p>
            <table class="w-full text-left text-sm"><thead><tr><th class="p-2">Kode</th><th class="p-2">Nama</th><th class="p-2">Kategori</th><th class="p-2">Satuan</th></tr></thead><tbody>@forelse ($items as $item)<tr class="border-t border-slate-200"><td class="p-2">{{ $item->code }}</td><td class="p-2">{{ $item->name }}</td><td class="p-2">{{ $item->category->name }}</td><td class="p-2">{{ $item->unit }}</td></tr>@empty<tr><td colspan="4" class="p-6 text-slate-500">Belum ada master barang. Tambahkan kategori lalu buat master barang.</td></tr>@endforelse</tbody></table>{{ $items->links() }}
        </section>
        @if (auth()->user()->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL]))
        <form method="POST" action="{{ route('master.barang.store') }}" class="space-y-4 rounded-xl border border-slate-200 bg-white p-5">
            @csrf
            <h3 class="font-semibold">Tambah Master Barang</h3>
            <div><label for="code" class="mb-1 block text-sm">Kode Barang</label><input id="code" name="code" value="{{ old('code') }}" maxlength="50" placeholder="Otomatis: BRG-000001" class="w-full rounded-lg border border-slate-300 p-2"><p class="mt-1 text-xs text-slate-600">Kosongkan untuk kode otomatis. Kode lama, termasuk nol awal, dapat dimasukkan.</p></div>
            <div><label for="name" class="mb-1 block text-sm">Nama Barang</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="255" class="w-full rounded-lg border border-slate-300 p-2"></div>
            <div><label for="category" class="mb-1 block text-sm">Kategori</label><select id="category" name="asset_category_id" required class="w-full rounded-lg border border-slate-300 p-2"><option value="">Pilih kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('asset_category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
            <div><label for="unit" class="mb-1 block text-sm">Satuan</label><input id="unit" name="unit" value="{{ old('unit', 'Unit') }}" required maxlength="50" class="w-full rounded-lg border border-slate-300 p-2"></div>
            <div><label for="specification" class="mb-1 block text-sm">Spesifikasi</label><textarea id="specification" name="specification" rows="3" class="w-full rounded-lg border border-slate-300 p-2">{{ old('specification') }}</textarea></div>
            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-white">Simpan Master Barang</button>
        </form>
        @endif
    </div>
@endsection

