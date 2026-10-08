@extends('layouts.app-shell')

@section('page-title', 'Laporan Inventaris')
@section('header-title', 'Laporan Inventaris')

@section('content')
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Total</div>
            <div class="mt-3 text-2xl font-bold text-slate-900">{{ $summary['total'] }}</div>
            <div class="text-sm text-slate-600">Aset</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Baik</div>
            <div class="mt-3 text-2xl font-bold text-emerald-600">{{ $summary['baik'] }}</div>
            <div class="text-sm text-slate-600">Kondisi</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Rusak</div>
            <div class="mt-3 text-2xl font-bold text-amber-600">{{ $summary['rusak'] }}</div>
            <div class="text-sm text-slate-600">Kondisi</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Dipakai</div>
            <div class="mt-3 text-2xl font-bold text-sky-600">{{ $summary['dipakai'] }}</div>
            <div class="text-sm text-slate-600">Status</div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Ringkasan per Kategori</h2>
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-3 py-2">Kategori</th>
                        <th class="px-3 py-2 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($categorySummary as $category)
                        <tr>
                            <td class="px-3 py-2 font-medium text-slate-800">{{ $category->name }}</td>
                            <td class="px-3 py-2 text-right">{{ $category->assets_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-3 py-4 text-center text-slate-500">Belum ada kategori.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Ringkasan per Lokasi</h2>
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-3 py-2">Lokasi</th>
                        <th class="px-3 py-2 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($locationSummary as $location)
                        <tr>
                            <td class="px-3 py-2 font-medium text-slate-800">{{ $location->name }}</td>
                            <td class="px-3 py-2 text-right">{{ $location->assets_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-3 py-4 text-center text-slate-500">Belum ada lokasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection

@section('headerAction')
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('inventaris.index') }}" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white">Kembali</a>
    </div>
@endsection
