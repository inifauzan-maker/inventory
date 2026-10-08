<div class="md:col-span-2">
    <label for="catalog-item" class="mb-1 block text-sm font-medium text-slate-700">Kode Barang / Master Barang</label>
    <select id="catalog-item" name="catalog_item_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-sky-500">
        <option value="">Belum terhubung — isi nama dan kategori manual</option>
        @foreach ($catalogItems as $item)<option value="{{ $item->id }}" data-name="{{ $item->name }}" data-category="{{ $item->asset_category_id }}" @selected(old('catalog_item_id', isset($asset) ? $asset->catalog_item_id : null) == $item->id)>{{ $item->code }} — {{ $item->name }}</option>@endforeach
    </select>
    <p class="mt-1 text-xs text-slate-500">Pilih master untuk mengisi nama dan kategori otomatis. Satu kode barang dapat digunakan banyak aset.</p>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('catalog-item');
    const name = document.querySelector('input[name="name"]');
    const category = document.querySelector('select[name="asset_category_id"]');
    const sync = () => {
        const option = select.selectedOptions[0];
        if (select.value) {
            name.value = option.dataset.name;
            category.value = option.dataset.category;
        }
        name.readOnly = Boolean(select.value);
        category.style.pointerEvents = select.value ? 'none' : '';
        category.setAttribute('aria-readonly', select.value ? 'true' : 'false');
    };
    select.addEventListener('change', sync);
    sync();
});
</script>

