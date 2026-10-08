<section class="mb-6 rounded-xl border border-sky-200 bg-sky-50 p-5">
    <h3 class="font-semibold">Alur Aktivasi Barang</h3>
    <p class="mt-2 text-sm">Diterima → Diperiksa → Dokumen diverifikasi → Inventaris dicatat dan nomor diterbitkan → Label dicetak → Label ditempel → Penempatan → Serah terima → Aktif</p>
    <p class="mt-3 font-semibold text-sky-800">{{ App\AssetActivationService::STAGES[$asset->activation_stage] ?? $asset->activation_stage }}</p>
    @if ($asset->verification_confirmed_at)<p class="mt-1 text-sm">Verifikasi sebelum penerbitan nomor: {{ $asset->verification_confirmed_at->format('d/m/Y H:i') }}</p>@endif
    @if ($asset->activation_stage !== 'active' && auth()->user()->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL]))
        @php($step = ['registered' => 'print', 'label_printed' => 'attach', 'label_attached' => 'place', 'placed' => 'handover'][$asset->activation_stage] ?? null)
        @if ($step)
        <form method="POST" action="{{ route('aset.activation', [$asset, $step]) }}" class="mt-4 space-y-3">
            @csrf
            @if ($step === 'print')
                <a href="{{ route('aset.label', $asset) }}" target="_blank" rel="noopener" class="inline-block rounded-lg bg-sky-600 px-4 py-2 text-sm text-white">Buka dan Cetak Label</a>
                <p class="text-sm">Setelah hasil cetak tersedia, konfirmasikan di bawah.</p>
            @elseif ($step === 'attach')
                <p class="text-sm">Tempelkan label pada unit fisik yang sesuai nomor inventaris.</p>
            @elseif ($step === 'place')
                <div><label for="activation-location" class="mb-1 block text-sm">Lokasi Penempatan</label><select id="activation-location" name="location_id" required class="w-full rounded-lg border border-slate-300 p-2">@foreach ($locations as $location)<option value="{{ $location->id }}" @selected($asset->location_id === $location->id)>{{ $location->name }}</option>@endforeach</select></div>
                <div><label for="activation-room" class="mb-1 block text-sm">Ruangan</label><select id="activation-room" name="room_id" class="w-full rounded-lg border border-slate-300 p-2"><option value="">Tanpa ruangan</option>@foreach ($rooms as $room)<option value="{{ $room->id }}" data-location="{{ $room->location_id }}">{{ $room->location?->name }} — {{ $room->name }}</option>@endforeach</select></div>
                <script>
                const placeLocation = document.getElementById('activation-location');
                const placeRoom = document.getElementById('activation-room');
                const filterRooms = () => { Array.from(placeRoom.options).forEach(option => { const invalid = option.value && option.dataset.location !== placeLocation.value; option.hidden = Boolean(invalid); option.disabled = Boolean(invalid); }); if (placeRoom.selectedOptions[0]?.disabled) placeRoom.value = ''; };
                placeLocation.addEventListener('change', filterRooms); filterRooms();
                </script>
            @elseif ($step === 'handover')
                <div><label for="recipient-name" class="mb-1 block text-sm">Nama Pengguna / Penerima</label><input id="recipient-name" name="recipient_name" required maxlength="255" class="w-full rounded-lg border border-slate-300 p-2" value="{{ old('recipient_name') }}"></div>
                <div><label for="handover-reference" class="mb-1 block text-sm">Nomor Berita Acara / Referensi Serah Terima</label><input id="handover-reference" name="handover_reference" required maxlength="255" class="w-full rounded-lg border border-slate-300 p-2" value="{{ old('handover_reference') }}"></div>
            @endif
            <div><label for="activation-notes" class="mb-1 block text-sm">Catatan Tahap</label><textarea id="activation-notes" name="notes" rows="2" class="w-full rounded-lg border border-slate-300 p-2">{{ old('notes') }}</textarea></div>
            <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" class="mt-1"><span>{{ ['print' => 'Label sudah selesai dicetak.', 'attach' => 'Label sudah ditempel pada aset fisik.', 'place' => 'Barang sudah ditempatkan pada lokasi dan ruangan terpilih.', 'handover' => 'Barang sudah diserahkan dan diterima pengguna sesuai berita acara.'][$step] }}</span></label>
            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm text-white">{{ $step === 'handover' ? 'Selesaikan Serah Terima dan Aktifkan' : 'Konfirmasi Tahap Selesai' }}</button>
        </form>
        @endif
    @endif
    @if ($asset->recipient_name)<p class="mt-3 text-sm">Penerima: {{ $asset->recipient_name }} · Referensi: {{ $asset->handover_reference }} · {{ $asset->handed_over_at?->format('d/m/Y H:i') }}</p>@endif
    @if ($asset->handed_over_at && auth()->user()->hasAnyRole([App\Models\User::ROLE_SUPERADMIN, App\Models\User::ROLE_OPERASIONAL, App\Models\User::ROLE_DIREKSI, App\Models\User::ROLE_KEUANGAN]))
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('aset.bast', $asset) }}" target="_blank" rel="noopener" class="rounded border px-4 py-2 text-sm">Cetak BAST / PDF</a>
            <a href="{{ route('aset.bast.docx', $asset) }}" class="rounded bg-sky-600 px-4 py-2 text-sm text-white">Unduh Word BAST</a>
        </div>
    @endif
</section>

