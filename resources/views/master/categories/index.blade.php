@extends('layouts.app-shell')

@section('page-title', 'Master Kategori')
@section('header-title', 'Master Kategori')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h2 class="text-lg font-semibold">Daftar Kategori</h2>
            </div>
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Deskripsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $category->code }}</td>
                            <td class="px-4 py-3">{{ $category->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $category->description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-slate-500">Belum ada kategori.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Tambah Kategori</h2>
            <form method="POST" action="{{ route('master.kategori.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Kode</label>
                    <input type="text" name="code" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama</label>
                    <input type="text" name="name" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
                    <textarea name="description" rows="4" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                </div>
                <button type="submit" class="w-full rounded bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-500">Simpan Kategori</button>
            </form>
        </div>
    </div>
@endsection
