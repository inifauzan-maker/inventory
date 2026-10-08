@extends('layouts.app-shell')

@section('page-title', 'Penerimaan Barang')
@section('header-title', 'Penerimaan Barang')

@section('content')
    @if ($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <p class="mb-4 rounded-lg bg-sky-50 p-4 text-sm text-sky-900">Penerimaan belum menerbitkan nomor inventaris. Setelah disimpan, lakukan pemeriksaan barang dan verifikasi dokumen dari detail penerimaan.</p>
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('penerimaan.store') }}" class="space-y-6">
            @csrf
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">PO</label>
                    <select name="purchase_order_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                        <option value="">Pilih PO</option>
                        @foreach ($purchaseOrders as $purchaseOrder)
                            <option value="{{ $purchaseOrder->id }}">{{ $purchaseOrder->order_number }} - {{ $purchaseOrder->vendor_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nomor Penerimaan</label>
                    <input type="text" name="receipt_number" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal Terima</label>
                    <input type="date" name="received_at" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
                    <input type="text" name="notes" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <div class="rounded border border-dashed border-slate-300 p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Item diterima</h3>
                <div class="space-y-4">
                    @foreach ($purchaseOrders as $purchaseOrder)
                        @foreach ($purchaseOrder->items as $item)
                            <div data-po="{{ $purchaseOrder->id }}" class="grid gap-3 rounded border border-slate-200 p-3 md:grid-cols-7">
                                <label class="md:col-span-7 flex items-center gap-2 text-sm"><input type="checkbox" data-receive-item checked> Catat penerimaan item ini</label>
                                <input type="hidden" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][purchase_order_item_id]" value="{{ $item->id }}">
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Item</label>
                                    <input type="text" value="{{ $item->item_name }}" class="w-full rounded border border-slate-200 bg-slate-50 px-2 py-2" readonly>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Qty</label>
                                    <input type="number" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][quantity_received]" min="1" value="1" class="w-full rounded border border-slate-300 px-2 py-2" required>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Nomor Aset</label>
                                    <input type="text" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][asset_number]" readonly placeholder="Otomatis setelah verifikasi" class="w-full rounded border border-slate-300 bg-slate-50 px-2 py-2">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Nama Aset</label>
                                    <input type="text" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][asset_name]" value="{{ $item->item_name }}" class="w-full rounded border border-slate-300 px-2 py-2">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Kategori</label>
                                    <select name="items[{{ $loop->parent->index }}_{{ $loop->index }}][asset_category_id]" class="w-full rounded border border-slate-300 px-2 py-2">
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Lokasi</label>
                                    <select name="items[{{ $loop->parent->index }}_{{ $loop->index }}][location_id]" class="w-full rounded border border-slate-300 px-2 py-2">
                                        @foreach ($locations as $location)
                                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded bg-emerald-600 px-5 py-2.5 font-medium text-white hover:bg-emerald-500">Simpan Penerimaan</button>
            </div>
        </form>
    </div>
    <script>
    const receiptPo = document.querySelector('select[name="purchase_order_id"]');
    const updateReceiptRows = () => { document.querySelectorAll('[data-po]').forEach(row => { const selected = row.dataset.po === receiptPo.value; row.hidden = !selected; const checkbox = row.querySelector('[data-receive-item]'); row.querySelectorAll('input,select,textarea').forEach(input => { input.disabled = !selected || (input !== checkbox && !checkbox.checked); }); }); };
    receiptPo.addEventListener('change', updateReceiptRows);
    document.querySelectorAll('[data-receive-item]').forEach(checkbox => checkbox.addEventListener('change', updateReceiptRows));
    updateReceiptRows();
    </script>
@endsection
