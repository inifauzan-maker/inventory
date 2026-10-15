<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', 'SIIP')</title>
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
                    <a href="{{ route('pengajuan.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('pengajuan.*') ? 'bg-sky-500/20 text-sky-100' : '' }}">Pengajuan Barang</a>
                    <a href="{{ route('inventaris.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white {{ request()->routeIs('inventaris.index') ? 'bg-sky-500/20 text-sky-100 ring-1 ring-sky-400/30' : '' }}">
                        <span>Dashboard</span>
                        @if (request()->routeIs('inventaris.index'))
                            <span class="rounded-full bg-sky-500/20 px-2 py-0.5 text-xs">Live</span>
                        @endif
                    </a>

                    <a href="{{ route('audit.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white {{ request()->routeIs('audit.*') ? 'bg-sky-500/20 text-sky-100 ring-1 ring-sky-400/30' : '' }}">Audit Trail</a>
                    <a href="{{ route('laporan.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white {{ request()->routeIs('laporan.index') ? 'bg-sky-500/20 text-sky-100 ring-1 ring-sky-400/30' : '' }}">Laporan</a>

                    @if ($user && $user->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_KACAB, App\Models\User::ROLE_DIREKSI, App\Models\User::ROLE_OPERASIONAL]))
                        <div class="space-y-1 rounded-xl border border-slate-700 bg-slate-800/30 p-2">
                            <div class="px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Master Data</div>
                            <a href="{{ route('master.barang.index') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800 hover:text-white">Kode Barang</a>
                            <a href="{{ route('master.nomor.index') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800 hover:text-white">Nomor Inventaris</a>
                            <a href="{{ route('master.kategori.index') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white {{ request()->routeIs('master.kategori.*') ? 'bg-sky-500/20 text-sky-100 ring-1 ring-sky-400/30' : '' }}">Kategori</a>
                            <a href="{{ route('master.lokasi.index') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white {{ request()->routeIs('master.lokasi.*') ? 'bg-sky-500/20 text-sky-100 ring-1 ring-sky-400/30' : '' }}">Lokasi</a>
                            <a href="{{ route('master.ruangan.index') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white {{ request()->routeIs('master.ruangan.*') ? 'bg-sky-500/20 text-sky-100 ring-1 ring-sky-400/30' : '' }}">Ruangan</a>
                        </div>
                    @endif

                    @if ($user && $user->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL]))
                        <a href="{{ route('mutasi.create') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white {{ request()->routeIs('mutasi.create') ? 'bg-sky-500/20 text-sky-100 ring-1 ring-sky-400/30' : '' }}">Mutasi</a>
                    @endif

                    @if ($user && $user->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL, App\Models\User::ROLE_KEUANGAN]))
                        <a href="{{ route('pengadaan.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white {{ request()->routeIs('pengadaan.*') ? 'bg-sky-500/20 text-sky-100 ring-1 ring-sky-400/30' : '' }}">Pengadaan</a>
                    @endif

                    @if ($user && $user->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL, App\Models\User::ROLE_STAFF, App\Models\User::ROLE_AKADEMIK]))
                        <a href="{{ route('aset.create') }}" class="block rounded-xl bg-emerald-600 px-3 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-500 {{ request()->routeIs('aset.create') ? 'bg-emerald-500' : '' }}">Tambah Aset</a>
                    @endif
                    <a href="{{ route('bantuan.index') }}" @if(request()->routeIs('bantuan.*')) aria-current="page" @endif class="block rounded-xl px-3 py-2.5 text-sm text-slate-300 hover:bg-slate-800 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-400 {{ request()->routeIs('bantuan.*') ? 'bg-sky-500/20 text-sky-100 ring-1 ring-sky-400/30' : '' }}">Bantuan</a>
                </nav>

                <div class="mt-auto rounded-2xl border border-slate-700 bg-slate-800/60 p-4">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">User</div>
                    <div class="mt-3 text-sm font-semibold text-white">{{ $user?->name ?? 'Guest' }}</div>
                    <div class="text-xs text-slate-300">{{ $user?->roleLabel() ?? '-' }}</div>
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
                    <div class="flex flex-wrap items-center gap-3">
                        <button id="openSidebar" type="button" class="rounded-lg border border-slate-200 p-2 text-slate-700 hover:bg-slate-100 md:hidden">☰</button>
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500">SIIP</p>
                            <h2 class="text-lg font-bold text-slate-900">@yield('header-title', 'Dashboard')</h2>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @hasSection('headerAction')
                            @yield('headerAction')
                        @elseif (isset($headerAction) && $headerAction)
                            {{ $headerAction }}
                        @endif
                    </div>
                </div>
            </header>

            <main class="p-4 md:p-6">
                <div class="mx-auto max-w-7xl">
                    @yield('content')
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
