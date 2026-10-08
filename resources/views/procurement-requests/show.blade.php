@extends('layouts.app-shell')
@section('page-title', 'Detail Pengajuan')
@section('header-title', 'Detail Pengajuan')
@section('headerAction')
    <div class="flex flex-wrap gap-2"><a href="{{ route('pengajuan.letter', $proposal) }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cetak Surat / PDF</a><a href="{{ route('pengajuan.document', $proposal) }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm text-white">Unduh Word</a></div>
@endsection
@section('content')
    <div class="space-y-5">
        @if (session('success'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="text-lg font-semibold">{{ $proposal->letter_number }}</h3><p class="mt-1 font-medium">{{ $proposal->subject }}</p>
            <p class="mt-2 text-sm">{{ $proposal->requester_name }} · {{ $proposal->division }} · {{ $proposal->request_date->format('d/m/Y') }} · Versi {{ $proposal->version }}</p>
            <p class="mt-3 font-semibold text-sky-700">{{ App\Models\ProcurementRequest::statusLabels()[$proposal->status] }}</p>
            <p class="mt-3 whitespace-pre-line">{{ $proposal->purpose }}</p>
            @if ($proposal->notes)<p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $proposal->notes }}</p>@endif
            @can('update', $proposal)
                <div class="mt-4 flex flex-wrap gap-3"><a href="{{ route('pengajuan.edit', $proposal) }}" class="rounded-lg border border-slate-300 px-4 py-2">Ubah Draf</a><form method="POST" action="{{ route('pengajuan.submit', $proposal) }}">@csrf<button class="rounded-lg bg-emerald-600 px-4 py-2 text-white">Kirim ke Keuangan</button></form></div>
            @endcan
            @if ($proposal->status === 'rejected')<p class="mt-3 text-sm text-red-700">Penolakan menutup surat ini. Buat pengajuan baru untuk perubahan; riwayat surat lama tetap tersimpan.</p>@endif
        </section>
        @if ($proposal->status === 'approved' && auth()->user()->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL]))
            <a href="{{ route('pengadaan.index', ['proposal' => $proposal->id]) }}" class="inline-block rounded bg-emerald-600 px-4 py-2 text-white">Buat PO dari Pengajuan</a>
        @endif
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="font-semibold">PO dari Pengajuan</h3>
            @forelse ($proposal->purchaseOrders as $linkedOrder)
                <p class="mt-2"><a class="text-sky-700" href="{{ route('pengadaan.show', $linkedOrder) }}">{{ $linkedOrder->order_number }}</a> � {{ $linkedOrder->status }}</p>
            @empty
                <p class="mt-2 text-sm text-slate-600">Belum ada PO terhubung.</p>
            @endforelse
        </section>
        @include('procurement-requests.items', ['proposal' => $proposal])
        @foreach (['finance' => ['Keuangan', 'reviewFinance'], 'director' => ['Direksi', 'reviewDirector']] as $level => [$label, $ability])
            @can($ability, $proposal)
                <section class="rounded-xl border border-slate-200 bg-white p-5">
                    <h3 class="font-semibold">Pemeriksaan {{ $label }}</h3>
                    <form method="POST" action="{{ route('pengajuan.review', [$proposal, $level]) }}" class="mt-4 space-y-3">
                        @csrf
                        @if ($level === 'finance' && $proposal->selection_mode === 'alternatives')
                            <label class="block text-sm" for="selected-item">Rekomendasi Alternatif</label><select id="selected-item" name="selected_item_id" class="w-full rounded-lg border border-slate-300 p-2"><option value="">Pilih satu alternatif untuk persetujuan</option>@foreach ($proposal->items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
                        @endif
                        <label for="review-notes-{{ $level }}" class="block text-sm">Catatan (wajib untuk penolakan)</label><textarea id="review-notes-{{ $level }}" name="notes" rows="3" maxlength="5000" class="w-full rounded-lg border border-slate-300 p-2">{{ old('notes') }}</textarea>
                        <div class="flex flex-wrap gap-3"><button name="decision" value="approved" class="rounded-lg bg-emerald-600 px-4 py-2 text-white">{{ $level === 'finance' ? 'Setujui dan Teruskan ke Direksi' : 'Setujui Pengajuan' }}</button><button name="decision" value="rejected" class="rounded-lg bg-red-600 px-4 py-2 text-white">Tolak Pengajuan</button></div>
                    </form>
                </section>
            @endcan
        @endforeach
        <section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="font-semibold">Riwayat Persetujuan</h3><p class="mt-2 text-sm text-slate-600">Dikirim: {{ $proposal->submitted_at?->format('d/m/Y H:i') ?? 'Belum dikirim' }}</p>
            @forelse ($proposal->decisions as $decision)<div class="mt-3 border-t border-slate-200 pt-3 text-sm"><p class="font-medium">{{ $decision->level === 'finance' ? 'Keuangan' : 'Direksi' }} · {{ $decision->reviewer_name }} · {{ $decision->decision === 'approved' ? 'Disetujui' : 'Ditolak' }}</p><p>{{ $decision->decided_at->format('d/m/Y H:i') }} · Versi {{ $decision->version }}</p>@if ($decision->selectedItem)<p>Rekomendasi: {{ $decision->selectedItem->name }}</p>@endif<p class="whitespace-pre-line">{{ $decision->notes }}</p></div>@empty<p class="mt-2 text-sm text-slate-500">Belum ada keputusan.</p>@endforelse
        </section>
        <a href="{{ route('pengajuan.index') }}" class="inline-block text-sky-700 underline">Kembali ke daftar pengajuan</a>
    </div>
@endsection

