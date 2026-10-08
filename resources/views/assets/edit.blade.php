@extends('layouts.app-shell')

@section('page-title', 'Edit Aset')
@section('header-title', 'Edit Aset')

@section('content')
    @if ($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('aset.update', $asset) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="grid gap-5 md:grid-cols-2">
                @include('assets.catalog-select')
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nomor Inventaris</label>
                    <input type="text" name="asset_number" value="{{ $asset->asset_number }}" readonly class="w-full rounded border border-slate-300 bg-slate-50 px-3 py-2">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Kategori</label>
                    <select name="asset_category_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ old('asset_category_id', $asset->asset_category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama Barang</label>
                    <input type="text" name="name" value="{{ old('name', $asset->name) }}" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Serial Number</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Lokasi</label>
                    <select name="location_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" {{ old('location_id', $asset->location_id) == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Ruangan</label>
                    <select name="room_id" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <option value="">Tidak ada</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" {{ old('room_id', $asset->room_id) == $room->id ? 'selected' : '' }}>{{ $room->location?->name ?? '-' }} - {{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Kondisi</label>
                    <input type="text" name="condition" value="{{ old('condition', $asset->condition) }}" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Status Penggunaan</label>
                    <input type="text" name="usage_status" value="{{ old('usage_status', $asset->usage_status) }}" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Harga Beli</label>
                    <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $asset->purchase_price) }}" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal Perolehan</label>
                    <input type="date" name="acquired_at" value="{{ old('acquired_at', $asset->acquired_at?->format('Y-m-d')) }}" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
                    <textarea name="description" rows="4" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">{{ old('description', $asset->description) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded bg-emerald-600 px-5 py-2.5 font-medium text-white hover:bg-emerald-500">Update Aset</button>
            </div>
        </form>
    </div>
@endsection
