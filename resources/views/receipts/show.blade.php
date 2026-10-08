@extends('layouts.app-shell')

@section('page-title', 'Detail Penerimaan')
@section('header-title', 'Detail Penerimaan')

@section('content')
    @if (session('success'))<div role="status" class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
    @if ($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @include('receipts.workflow')
    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Nomor</div>
            <div class="mt-2 text-xl font-bold text-slate-900">{{ $receipt->receipt_number }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">PO</div>
            <div class="mt-2 text-xl font-bold text-sky-700">{{ $receipt->purchaseOrder?->order_number ?? '-' }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Tanggal</div>
            <div class="mt-2 text-xl font-bold text-slate-900">{{ $receipt->received_at?->format('d-m-Y') ?? '-' }}</div>
        </div>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Item Diterima</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-3 py-2">Item</th>
                        <th class="px-3 py-2">Qty</th>
                        <th class="px-3 py-2">Nomor Aset</th>
                        <th class="px-3 py-2">Kategori</th>
                        <th class="px-3 py-2">Lokasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($receipt->items as $item)
                        <tr>
                            <td class="px-3 py-2 font-medium text-slate-800">{{ $item->item_name }}</td>
                            <td class="px-3 py-2">{{ $item->quantity_received }}</td>
                            <td class="px-3 py-2">@forelse ($item->assets as $asset)<a class="block text-sky-700 underline" href="{{ route('aset.show', $asset) }}">{{ $asset->asset_number }}</a>@empty{{ $receipt->workflow_stage === 'legacy' ? ($item->asset_number ?? '-') : 'Belum diterbitkan' }}@endforelse</td>
                            <td class="px-3 py-2">{{ $item->category?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->location?->name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
