@extends('layouts.app-shell')
@section('page-title', 'Pengajuan Barang')
@section('header-title', 'Pengajuan Barang')
@section('headerAction')
    <a href="{{ route('pengajuan.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Buat Pengajuan</a>
@endsection
@section('content')
    <div class="space-y-5">
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">Alur: Pengaju → Keuangan → Direksi. Pengajuan belum menjadi PO atau aset. Anda dapat menyunting draf; surat yang sudah dikirim tidak dapat diubah.</div>
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div><label for="status" class="mb-1 block text-sm">Status pengajuan</label><select id="status" name="status" class="rounded-lg border border-slate-300 p-2"><option value="">Semua status</option>@foreach (App\Models\ProcurementRequest::statusLabels() as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></div>
            <button class="rounded-lg bg-sky-600 px-4 py-2 text-white">Filter</button>
        </form>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full text-left text-sm"><thead class="bg-slate-50"><tr><th class="p-3">Nomor / Perihal</th><th class="p-3">Pengaju</th><th class="p-3">Tanggal</th><th class="p-3">Status</th></tr></thead><tbody class="divide-y divide-slate-200">
            @forelse ($proposals as $proposal)
                <tr><td class="p-3"><a class="font-medium text-sky-700 underline" href="{{ route('pengajuan.show', $proposal) }}">{{ $proposal->letter_number }}</a><p>{{ $proposal->subject }}</p></td><td class="p-3">{{ $proposal->requester_name }}<p class="text-slate-500">{{ $proposal->division }}</p></td><td class="p-3">{{ $proposal->request_date->format('d/m/Y') }}</td><td class="p-3">{{ App\Models\ProcurementRequest::statusLabels()[$proposal->status] }}</td></tr>
            @empty
                <tr><td colspan="4" class="p-8 text-center text-slate-500">Belum ada pengajuan yang dapat Anda lihat.</td></tr>
            @endforelse
            </tbody></table>
        </div>
        {{ $proposals->links() }}
    </div>
@endsection

