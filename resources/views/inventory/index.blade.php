<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaris SIIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">
    @php
        $user = Auth::user();
    @endphp

    <div class="min-h-screen bg-slate-100">
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-slate-200 bg-slate-900 text-slate-100 transition-transform duration-200 md:translate-x-0">
            <div class="flex h-full flex-col p-5">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-[0.35em] text-slate-400">SIIP</div>
                        <h1 class="mt-2 text-xl font-bold text-white">Inventaris</h1>
                    </div>
                    <button id="closeSidebar" type="button" class="rounded-lg border border-slate-700 p-2 text-slate-300 hover:bg-slate-800 md:hidden">✕</button>
                </div>

                <nav class="min-h-0 flex-1 space-y-2 overflow-y-auto pb-4" aria-label="Menu utama">
                    <a href="{{ route('pengajuan.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 hover:bg-slate-800 hover:text-white">Pengajuan Barang</a>
                    <a href="{{ route('inventaris.index') }}" class="flex items-center justify-between rounded-xl bg-sky-500/20 px-3 py-2.5 text-sm font-medium text-sky-100 ring-1 ring-sky-400/30">
                        <span>Dashboard</span>
                        <span class="rounded-full bg-sky-500/20 px-2 py-0.5 text-xs">{{ $summary['total'] }}</span>
                    </a>

                    <a href="{{ route('laporan.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white">Laporan</a>

                    @if ($user && $user->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_KACAB, App\Models\User::ROLE_DIREKSI, App\Models\User::ROLE_OPERASIONAL]))
                        <a href="{{ route('master.barang.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white">Master Barang</a>
                        <a href="{{ route('master.nomor.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white">Nomor Inventaris</a>
                    @endif

                    @if ($user && $user->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL]))
                        <a href="{{ route('mutasi.create') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white">Mutasi</a>
                    @endif

                    @if ($user && $user->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL, App\Models\User::ROLE_KEUANGAN]))
                        <a href="{{ route('pengadaan.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white">Pengadaan</a>
                    @endif

                    @if ($user && $user->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL, App\Models\User::ROLE_STAFF, App\Models\User::ROLE_AKADEMIK]))
                        <a href="{{ route('aset.create') }}" class="block rounded-xl bg-emerald-600 px-3 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-500">Tambah Aset</a>
                    @endif
                    <a href="{{ route('bantuan.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 hover:bg-slate-800 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-400">Bantuan</a>
                </nav>

                <div class="mt-auto rounded-2xl border border-slate-700 bg-slate-800/60 p-4">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">User</div>
                    <div class="mt-3 text-sm font-semibold text-white">{{ $user->name }}</div>
                    <div class="text-xs text-slate-300">{{ $user->roleLabel() }}</div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-900 transition hover:bg-slate-200">Logout</button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="md:ml-72">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/80 backdrop-blur-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 md:px-6">
                    <div class="flex items-center gap-3">
                        <button id="openSidebar" type="button" class="rounded-lg border border-slate-200 p-2 text-slate-700 hover:bg-slate-100 md:hidden">☰</button>
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500">SIIP</p>
                            <h2 class="text-lg font-bold text-slate-900">Dashboard</h2>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="hidden rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700 md:inline-flex">
                            {{ $assets->count() }} aset
                        </span>
                    </div>
                </div>
            </header>

            <main class="p-4 md:p-6">
                <div class="mb-6 grid gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Ringkasan</div>
                        <div class="mt-3 text-2xl font-bold text-slate-900">{{ $summary['total'] }}</div>
                        <div class="text-sm text-slate-600">Total Aset</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Kondisi</div>
                        <div class="mt-3 text-2xl font-bold text-emerald-600">{{ $summary['baik'] }}</div>
                        <div class="text-sm text-slate-600">Baik</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Kondisi</div>
                        <div class="mt-3 text-2xl font-bold text-amber-600">{{ $summary['rusak'] }}</div>
                        <div class="text-sm text-slate-600">Rusak</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Status</div>
                        <div class="mt-3 text-2xl font-bold text-sky-600">{{ $summary['dipakai'] }}</div>
                        <div class="text-sm text-slate-600">Dipakai</div>
                    </div>
                </div>

                <div class="mb-6 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-600">KPI</h3>
                            <span class="rounded-full bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-700">{{ $summary['total'] }} aset</span>
                        </div>
                        <div class="space-y-3">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Kategori</div>
                            @forelse ($categorySummary as $category)
                                <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                                    <span class="text-sm font-medium text-slate-700">{{ $category->name }}</span>
                                    <span class="rounded-full bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-700">{{ $category->assets_count }}</span>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500">Belum ada kategori.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-600">Lokasi</h3>
                            <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">{{ $locations->count() }} lokasi</span>
                        </div>
                        <div class="space-y-3">
                            @forelse ($locationSummary as $location)
                                <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                                    <span class="text-sm font-medium text-slate-700">{{ $location->name }}</span>
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">{{ $location->assets_count }}</span>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500">Belum ada lokasi.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <form method="GET" action="{{ route('inventaris.index') }}" class="grid gap-3 md:grid-cols-7">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Cari aset</label>
                            <input type="text" name="search" value="{{ old('search', $filters['search'] ?? '') }}" placeholder="Nomor, nama, serial" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-sky-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Kategori</label>
                            <select name="category_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-sky-500 focus:outline-none">
                                <option value="">Semua</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ (string) ($filters['category_id'] ?? '') === (string) $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Lokasi</label>
                            <select name="location_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-sky-500 focus:outline-none">
                                <option value="">Semua</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}" {{ (string) ($filters['location_id'] ?? '') === (string) $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Kondisi</label>
                            <select name="condition" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-sky-500 focus:outline-none">
                                <option value="">Semua</option>
                                <option value="baik" {{ ($filters['condition'] ?? '') === 'baik' ? 'selected' : '' }}>Baik</option>
                                <option value="rusak" {{ ($filters['condition'] ?? '') === 'rusak' ? 'selected' : '' }}>Rusak</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status</label>
                            <select name="usage_status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-sky-500 focus:outline-none">
                                <option value="">Semua</option>
                                <option value="dipakai" {{ ($filters['usage_status'] ?? '') === 'dipakai' ? 'selected' : '' }}>Dipakai</option>
                                <option value="cadangan" {{ ($filters['usage_status'] ?? '') === 'cadangan' ? 'selected' : '' }}>Cadangan</option>
                                <option value="perbaikan" {{ ($filters['usage_status'] ?? '') === 'perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2 md:col-span-2">
                            <button type="submit" class="w-full rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-500">Filter</button>
                            <a href="{{ route('inventaris.index') }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-center text-sm font-medium text-slate-700 hover:bg-slate-100">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-600">Inventaris</h3>
                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700 md:hidden">
                            {{ $assets->count() }} aset
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left">
                            <thead class="bg-slate-50 text-sm uppercase tracking-wide text-slate-600">
                                <tr>
                                    <th class="px-4 py-3">Nomor</th>
                                    <th class="px-4 py-3">Barang</th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3">Lokasi</th>
                                    <th class="px-4 py-3">Ruangan</th>
                                    <th class="px-4 py-3">Kondisi</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-sm">
                                @forelse ($assets as $asset)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $asset->asset_number }}<p class="mt-1 text-xs font-normal text-slate-500">{{ App\AssetActivationService::STAGES[$asset->activation_stage] ?? $asset->activation_stage }}</p></td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('aset.show', $asset) }}" class="font-medium text-sky-700 hover:text-sky-900">
                                                {{ $asset->name }}
                                            </a>
                                            @if ($asset->serial_number)
                                                <div class="text-xs text-slate-500">SN: {{ $asset->serial_number }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">{{ $asset->category?->name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $asset->location?->name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $asset->room?->name ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700">
                                                {{ $asset->condition }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-full bg-sky-100 px-2 py-1 text-xs font-medium text-sky-700">
                                                {{ $asset->usage_status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                            Belum ada data inventaris.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const openSidebar = document.getElementById('openSidebar');
        const closeSidebar = document.getElementById('closeSidebar');

        if (openSidebar) {
            openSidebar.addEventListener('click', function () {
                sidebar.classList.remove('-translate-x-full');
            });
        }

        if (closeSidebar) {
            closeSidebar.addEventListener('click', function () {
                sidebar.classList.add('-translate-x-full');
            });
        }
    </script>
</body>
</html>
