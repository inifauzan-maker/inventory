@extends('layouts.app-shell')

@section('page-title', 'Detail Aset')
@section('header-title', 'Detail Aset')

@section('content')
    @if (session('success'))<div role="status" class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
    @if ($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @include('assets.activation')
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Nomor</div>
            <div class="mt-3 text-xl font-bold text-slate-900">{{ $asset->asset_number }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Kondisi</div>
            <div class="mt-3 text-xl font-bold text-emerald-600">{{ $asset->condition }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Status</div>
            <div class="mt-3 text-xl font-bold text-sky-600">{{ $asset->usage_status }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Lokasi</div>
            <div class="mt-3 text-xl font-bold text-slate-900">{{ $asset->location?->name ?? '-' }}</div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.2fr_2fr]">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Informasi Aset</h2>
            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="text-slate-500">Kode Barang</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $asset->catalogItem?->code ?? 'Belum terhubung ke master barang' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Nama Barang</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $asset->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Kategori</dt>
                    <dd class="mt-1 text-slate-800">{{ $asset->category?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Ruangan</dt>
                    <dd class="mt-1 text-slate-800">{{ $asset->room?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Serial Number</dt>
                    <dd class="mt-1 text-slate-800">{{ $asset->serial_number ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Harga Beli</dt>
                    <dd class="mt-1 text-slate-800">{{ $asset->purchase_price ? 'Rp ' . number_format((float) $asset->purchase_price, 0, ',', '.') : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Tanggal Perolehan</dt>
                    <dd class="mt-1 text-slate-800">{{ $asset->acquired_at?->format('d-m-Y') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Deskripsi</dt>
                    <dd class="mt-1 text-slate-800">{{ $asset->description ?? '-' }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Riwayat Aktivitas</h2>
                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $asset->activities->count() }} log</span>
            </div>

            @if ($asset->activities->isEmpty())
                <div class="rounded border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500">
                    Belum ada log aktivitas untuk aset ini.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-3 py-2">Tanggal</th>
                                <th class="px-3 py-2">Aktivitas</th>
                                <th class="px-3 py-2">Detail</th>
                                <th class="px-3 py-2">Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($asset->activities as $activity)
                                <tr>
                                    <td class="px-3 py-2 text-slate-700">{{ $activity->occurred_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $activity->message }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $activity->details ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $activity->user?->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Riwayat Mutasi</h2>
                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $asset->mutations->count() }} log</span>
            </div>

            @if ($asset->mutations->isEmpty())
                <div class="rounded border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500">
                    Belum ada riwayat mutasi untuk aset ini.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-3 py-2">Tanggal</th>
                                <th class="px-3 py-2">Dari</th>
                                <th class="px-3 py-2">Ke</th>
                                <th class="px-3 py-2">Oleh</th>
                                <th class="px-3 py-2">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($asset->mutations as $mutation)
                                <tr>
                                    <td class="px-3 py-2 text-slate-700">{{ $mutation->moved_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-700">
                                        {{ $mutation->fromLocation?->name ?? '-' }} / {{ $mutation->fromRoom?->name ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-slate-700">
                                        {{ $mutation->toLocation?->name ?? '-' }} / {{ $mutation->toRoom?->name ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-slate-700">{{ $mutation->movedBy?->name ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $mutation->notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
