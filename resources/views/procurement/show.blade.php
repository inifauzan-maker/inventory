@extends('layouts.app-shell')

@section('page-title', 'Detail PO')
@section('header-title', 'Detail PO')

@section('headerAction')
    @if (auth()->user()->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL, App\Models\User::ROLE_DIREKSI, App\Models\User::ROLE_KEUANGAN]))
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pengadaan.letter', $purchaseOrder) }}" target="_blank" rel="noopener" class="rounded border px-4 py-2">Cetak Surat PO / PDF</a>
            <a href="{{ route('pengadaan.document', $purchaseOrder) }}" class="rounded bg-sky-600 px-4 py-2 text-white">Unduh Word PO</a>
        </div>
    @endif
@endsection
@section('content')
    @if ($purchaseOrder->procurementRequest)
        <p class="mb-4">Pengajuan sumber: <a class="text-sky-700" href="{{ route('pengajuan.show', $purchaseOrder->procurementRequest) }}">{{ $purchaseOrder->procurementRequest->letter_number }}</a> · Versi {{ $purchaseOrder->procurement_request_version }}</p>
    @endif
    @if ($errors->any())<div role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Nomor</div>
            <div class="mt-2 text-xl font-bold text-slate-900">{{ $purchaseOrder->order_number }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Vendor</div>
            <div class="mt-2 text-xl font-bold text-slate-900">{{ $purchaseOrder->vendor_name }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Total</div>
            <div class="mt-2 text-xl font-bold text-emerald-600">Rp {{ number_format($purchaseOrder->total_amount ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Status</div>
            <div class="mt-2 text-xl font-bold text-sky-600">{{ $purchaseOrder->status }}</div>
        </div>
    </div>

    @if (Auth::user() && Auth::user()->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_DIREKSI]))
        <div class="mb-6 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('pengadaan.approve', $purchaseOrder) }}">
                @csrf
                <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Setujui PO</button>
            </form>
            <form method="POST" action="{{ route('pengadaan.reject', $purchaseOrder) }}">
                @csrf
                <button type="submit" class="rounded bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-500">Tolak PO</button>
            </form>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1.3fr_0.9fr]">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Item PO</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-3 py-2">Item</th>
                            <th class="px-3 py-2">Qty</th>
                            <th class="px-3 py-2">Satuan</th>
                            <th class="px-3 py-2">Diterima</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($purchaseOrder->items as $item)
                            <tr>
                                <td class="px-3 py-2 font-medium text-slate-800">{{ $item->item_name }}</td>
                                <td class="px-3 py-2">{{ $item->quantity }}</td>
                                <td class="px-3 py-2">Rp {{ number_format($item->unit_price ?? 0, 0, ',', '.') }}</td>
                                <td class="px-3 py-2">{{ $item->received_quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Penerimaan</h2>
            @if ($purchaseOrder->receipts->isEmpty())
                <div class="rounded border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                    Belum ada penerimaan untuk PO ini.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($purchaseOrder->receipts as $receipt)
                        <a href="{{ route('penerimaan.show', $receipt) }}" class="block rounded-xl border border-slate-200 bg-slate-50 p-3 hover:border-sky-300 hover:bg-sky-50">
                            <div class="font-semibold text-sky-700">{{ $receipt->receipt_number }}</div>
                            <div class="mt-1 text-sm text-slate-600">{{ $receipt->received_at?->format('d-m-Y') ?? '-' }}</div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
