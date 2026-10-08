<section class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-5">
    <h3 class="mb-3 font-semibold">{{ $proposal->selection_mode === 'alternatives' ? 'Alternatif Barang — Pilih Satu' : 'Daftar Barang' }}</h3>
    <table class="w-full text-left text-sm"><thead><tr><th class="p-2">Nama / Spesifikasi</th><th class="p-2">Kategori</th><th class="p-2">Jumlah</th><th class="p-2">Harga Satuan</th><th class="p-2">Subtotal</th></tr></thead><tbody>
        @foreach ($proposal->items as $item)<tr class="border-t border-slate-200"><td class="p-2">{{ $item->name }}<p class="whitespace-pre-line text-slate-500">{{ $item->specification }}</p></td><td class="p-2">{{ $item->category }}</td><td class="p-2">{{ $item->quantity }} {{ $item->unit }}</td><td class="p-2">{{ $item->estimated_unit_price === null ? 'Belum diketahui' : 'Rp '.number_format((float) $item->estimated_unit_price, 2, ',', '.') }}</td><td class="p-2">{{ $item->subtotal === null ? 'Belum diketahui' : 'Rp '.number_format((float) $item->subtotal, 2, ',', '.') }}</td></tr>@endforeach
    </tbody></table>
    @if ($proposal->selection_mode === 'all')<p class="mt-3 font-semibold">Total estimasi: {{ $proposal->estimated_total === null ? 'Belum diketahui (ada harga kosong)' : 'Rp '.number_format((float) $proposal->estimated_total, 2, ',', '.') }}</p>@else<p class="mt-3 text-sm">Harga alternatif tidak dijumlahkan. Hanya satu barang akan direkomendasikan Keuangan.</p>@endif
</section>

