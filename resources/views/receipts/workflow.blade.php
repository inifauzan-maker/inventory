<section class="mb-6 rounded-xl border border-sky-200 bg-sky-50 p-5">
    <h3 class="font-semibold">Tahap Penerimaan dan Pemberian Nomor</h3>
    <p class="mt-2 font-medium text-sky-800">{{ App\Models\GoodsReceipt::workflowLabels()[$receipt->workflow_stage] ?? 'Penerimaan lama' }}</p>
    <p class="mt-2 text-sm">Pemeriksaan: {{ $receipt->inspected_at?->format('d/m/Y H:i') ?? 'Belum dicatat' }}<br>Dokumen: {{ $receipt->documents_verified_at?->format('d/m/Y H:i') ?? 'Belum diverifikasi' }} {{ $receipt->document_reference ? ' — '.$receipt->document_reference : '' }}</p>
    @if ($receipt->workflow_stage !== 'legacy' && auth()->user()->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL]))
        @php($step = ['received' => 'inspect', 'inspected' => 'documents', 'documents_verified' => 'register'][$receipt->workflow_stage] ?? null)
        @if ($step)
        <form method="POST" action="{{ route('penerimaan.workflow', [$receipt, $step]) }}" class="mt-4 space-y-3">
            @csrf
            @if ($step === 'inspect')<p class="text-sm">Periksa jumlah, kondisi fisik, jenis, dan spesifikasi barang terhadap PO. Lanjutkan hanya jika sesuai.</p>
            @elseif ($step === 'documents')<div><label for="document-reference" class="mb-1 block text-sm">Referensi Dokumen (surat jalan / faktur / bukti penerimaan)</label><input id="document-reference" name="document_reference" required maxlength="255" value="{{ old('document_reference') }}" class="w-full rounded-lg border border-slate-300 p-2"></div>
            @elseif ($step === 'register')
                <p class="text-sm">Lengkapi pencatatan di bawah. Satu nomor diterbitkan untuk setiap unit fisik, lalu lanjutkan label dan serah terima dari detail aset.</p>
                @foreach ($receipt->items as $index => $item)
                    <div class="rounded-lg border border-slate-200 bg-white p-3"><p class="mb-2 font-medium">{{ $item->item_name }} — {{ $item->quantity_received }} unit</p><input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}"><div class="grid gap-3 md:grid-cols-2"><div><label class="block text-sm" for="receipt-category-{{ $item->id }}">Kategori</label><select id="receipt-category-{{ $item->id }}" name="items[{{ $index }}][asset_category_id]" required class="w-full rounded-lg border border-slate-300 p-2"><option value="">Pilih kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected($item->asset_category_id === $category->id)>{{ $category->name }}</option>@endforeach</select></div><div><label class="block text-sm" for="receipt-location-{{ $item->id }}">Lokasi Pencatatan / Penyimpanan</label><select id="receipt-location-{{ $item->id }}" name="items[{{ $index }}][location_id]" required class="w-full rounded-lg border border-slate-300 p-2"><option value="">Pilih lokasi</option>@foreach ($locations as $location)<option value="{{ $location->id }}" @selected($item->location_id === $location->id)>{{ $location->name }}</option>@endforeach</select></div></div></div>
                @endforeach
            @endif
            <div><label for="receipt-step-notes" class="mb-1 block text-sm">Catatan</label><textarea id="receipt-step-notes" name="notes" rows="2" class="w-full rounded-lg border border-slate-300 p-2">{{ old('notes') }}</textarea></div>
            <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" class="mt-1"><span>{{ ['inspect' => 'Barang sudah diperiksa dan sesuai.', 'documents' => 'Dokumen sudah diperiksa dan valid.', 'register' => 'Data inventaris lengkap dan siap diberi nomor.'][$step] }}</span></label>
            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm text-white">{{ ['inspect' => 'Simpan Pemeriksaan Barang', 'documents' => 'Simpan Verifikasi Dokumen', 'register' => 'Catat Inventaris dan Generate Nomor'][$step] }}</button>
        </form>
        @endif
    @endif
</section>

