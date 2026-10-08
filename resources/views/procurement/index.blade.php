@extends('layouts.app-shell')

@section('page-title', 'Pengadaan')
@section('header-title', 'Pengadaan')
@section('content')
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <form method="GET" action="{{ route('pengadaan.index') }}" class="mb-5 flex flex-wrap gap-3">
        <label for="source-proposal">Pengajuan disetujui Direksi</label>
        <select id="source-proposal" name="proposal" class="rounded border p-2">
            <option value="">PO manual / belum terhubung</option>
            @foreach ($approvedProposals as $approved)
                <option value="{{ $approved->id }}" @selected($sourceProposal?->id === $approved->id)>{{ $approved->letter_number }} — {{ $approved->subject }}</option>
            @endforeach
        </select>
        <button class="rounded bg-sky-600 px-4 py-2 text-white">Isi dari Pengajuan</button>
    </form>
    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h2 class="text-lg font-semibold">Daftar PO</h2>
            </div>
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Nomor</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($purchaseOrders as $order)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-sky-700">
                                <a href="{{ route('pengadaan.show', $order) }}">{{ $order->order_number }}</a>
                            </td>
                            <td class="px-4 py-3">{{ $order->vendor_name }}</td>
                            <td class="px-4 py-3">Rp {{ number_format($order->total_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusClasses = [
                                        'draft' => 'bg-slate-100 text-slate-700',
                                        'approved' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-rose-100 text-rose-700',
                                        'received' => 'bg-sky-100 text-sky-700',
                                    ];
                                @endphp
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada pengadaan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Buat PO</h2>
            <form method="POST" action="{{ route('pengadaan.store') }}" class="space-y-4">
                @csrf
                @if ($sourceProposal)
                    <input type="hidden" name="procurement_request_id" value="{{ $sourceProposal->id }}">
                    <p class="text-sm">Sumber: <a href="{{ route('pengajuan.show', $sourceProposal) }}">{{ $sourceProposal->letter_number }}</a>. Draf PO ikut mencadangkan jumlah yang disetujui.</p>
                @endif
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nomor PO</label>
                    <input type="text" name="order_number" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Vendor</label>
                    <input type="text" name="vendor_name" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal</label>
                    <input type="date" name="order_date" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                    <input type="text" name="status" value="draft" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
                    <textarea name="notes" rows="3" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                </div>
                <div class="rounded border border-dashed border-slate-300 p-3">
                    <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-600">Item</h3>
                    <div class="space-y-3">
                        @if ($sourceProposal)
                            @foreach ($sourceItems as $sourceItem)
                                @if ($sourceItem->remaining_quantity > 0)
                                    <div class="grid gap-2 md:grid-cols-3" data-order-source-row>
                                        <label class="text-sm md:col-span-3"><input type="checkbox" checked data-order-include> Pesan barang ini</label>
                                        <input type="hidden" name="items[{{ $loop->index }}][procurement_request_item_id]" value="{{ $sourceItem->id }}">
                                        <input type="text" name="items[{{ $loop->index }}][item_name]" value="{{ $sourceItem->name }}" readonly class="rounded border p-2">
                                        <label class="text-sm">Jumlah (sisa {{ $sourceItem->remaining_quantity }} {{ $sourceItem->unit }})
                                            <input type="number" name="items[{{ $loop->index }}][quantity]" value="{{ old('items.'.$loop->index.'.quantity', $sourceItem->remaining_quantity) }}" min="1" max="{{ $sourceItem->remaining_quantity }}" required class="w-full rounded border p-2">
                                        </label>
                                        <label class="text-sm">Harga satuan PO
                                            <input type="number" name="items[{{ $loop->index }}][unit_price]" value="{{ old('items.'.$loop->index.'.unit_price', $sourceItem->estimated_unit_price) }}" step="0.01" min="0" required class="w-full rounded border p-2">
                                        </label>
                                    </div>
                                @endif
                            @endforeach
                            @if (! $sourceItems->contains(fn ($item) => $item->remaining_quantity > 0))<p>Seluruh jumlah sudah dialokasikan ke PO.</p>@endif
                        @else
                            <div class="grid gap-2 md:grid-cols-3">
                                <input type="text" name="items[0][item_name]" placeholder="Nama item" required class="rounded border p-2">
                                <input type="number" name="items[0][quantity]" placeholder="Qty" min="1" required class="rounded border p-2">
                                <input type="number" name="items[0][unit_price]" placeholder="Harga satuan" step="0.01" min="0" required class="rounded border p-2">
                            </div>
                        @endif
                    </div>
                </div>
                <button type="submit" class="w-full rounded bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-500">Simpan PO</button>
            </form>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-order-source-row]').forEach(row => {
            const include = row.querySelector('[data-order-include]');
            include.addEventListener('change', () => {
                row.querySelectorAll('input[name]').forEach(input => { input.disabled = !include.checked; });
            });
        });
    </script>
@endsection
