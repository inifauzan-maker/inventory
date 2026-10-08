@extends('layouts.app-shell')

@section('page-title', 'Mutasi Aset')
@section('header-title', 'Mutasi Aset')

@section('content')
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('mutasi.store') }}" class="space-y-6">
            @csrf
            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Aset</label>
                    <select name="asset_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                        <option value="">Pilih aset</option>
                        @foreach ($assets as $asset)
                            <option value="{{ $asset->id }}">{{ $asset->asset_number }} - {{ $asset->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Lokasi Asal</label>
                    <select name="from_location_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <option value="">Tidak diisi</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Lokasi Tujuan</label>
                    <select name="to_location_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <option value="">Tidak diisi</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Ruangan Asal</label>
                    <select name="from_room_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <option value="">Tidak diisi</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->location?->name ?? '-' }} - {{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Ruangan Tujuan</label>
                    <select name="to_room_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <option value="">Tidak diisi</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->location?->name ?? '-' }} - {{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
                    <textarea name="notes" rows="4" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded bg-emerald-600 px-5 py-2.5 font-medium text-white hover:bg-emerald-500">Simpan Mutasi</button>
            </div>
        </form>
    </div>
@endsection
