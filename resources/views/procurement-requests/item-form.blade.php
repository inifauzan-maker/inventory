<div data-item data-index="{{ $index }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <div class="grid gap-3 md:grid-cols-3">
        @foreach (['name' => ['Nama Barang', 'text'], 'category' => ['Kategori', 'text'], 'quantity' => ['Jumlah', 'number'], 'unit' => ['Satuan', 'text'], 'estimated_unit_price' => ['Estimasi Harga Satuan (Rp)', 'number']] as $field => [$label, $type])
            <div><label for="item-{{ $index }}-{{ $field }}" class="mb-1 block text-sm font-medium">{{ $label }}</label><input id="item-{{ $index }}-{{ $field }}" type="{{ $type }}" name="items[{{ $index }}][{{ $field }}]" value="{{ $item[$field] ?? '' }}" @if ($field !== 'estimated_unit_price') required @endif @if ($type === 'number') min="{{ $field === 'quantity' ? 1 : 0 }}" step="{{ $field === 'quantity' ? 1 : '0.01' }}" @endif class="w-full rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-sky-500"></div>
        @endforeach
        <div><label for="item-{{ $index }}-specification" class="mb-1 block text-sm font-medium">Spesifikasi / Keterangan</label><textarea id="item-{{ $index }}-specification" name="items[{{ $index }}][specification]" rows="2" class="w-full rounded-lg border border-slate-300 p-2">{{ $item['specification'] ?? '' }}</textarea></div>
    </div>
    <button type="button" data-remove-item class="mt-3 text-sm text-red-700 underline">Hapus baris</button>
</div>

