<?php

namespace App;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\CatalogItem;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssetImportService
{
    public const CONDITIONS = ['baik', 'rusak ringan', 'rusak berat', 'hilang', 'belum diperiksa'];

    public const STATUSES = ['digunakan' => 'dipakai', 'cadangan' => 'cadangan', 'dipinjamkan' => 'dipinjamkan', 'dalam perbaikan' => 'dalam perbaikan', 'tidak digunakan' => 'tidak digunakan', 'dihapuskan' => 'dihapuskan'];

    public static function mappingKey(string $type, string $value, string $location = ''): string
    {
        return hash('sha256', $type.'|'.mb_strtolower($value).'|'.mb_strtolower($location));
    }

    /** @param list<array> $rows */
    public function preview(array $rows, array $mapping = []): array
    {
        $categories = AssetCategory::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $rooms = Room::with('location')->orderBy('name')->get();
        $catalogItems = CatalogItem::orderBy('code')->get();
        $masters = ['category' => $categories, 'location' => $locations, 'room' => $rooms];
        $groups = [];
        $numbers = array_count_values(array_map(fn (array $row): string => mb_strtolower($row['values']['Nomor Inventaris']), $rows));
        $existing = [];
        foreach (array_chunk(array_column(array_column($rows, 'values'), 'Nomor Inventaris'), 500) as $chunk) {
            foreach (DB::table('issued_inventory_numbers')->whereIn('asset_number', $chunk)->pluck('asset_number') as $number) {
                $existing[mb_strtolower($number)] = true;
            }
            foreach (Asset::whereIn('asset_number', $chunk)->pluck('asset_number') as $number) {
                $existing[mb_strtolower($number)] = true;
            }
        }
        $prepared = [];
        foreach ($rows as $row) {
            $v = $row['values'];
            $errors = $row['errors'];
            $data = [
                'asset_number' => $v['Nomor Inventaris'],
                'name' => $v['Nama Barang'],
                'serial_number' => $v['Serial Number'] === '' ? null : $v['Serial Number'],
                'condition' => mb_strtolower($v['Kondisi']),
                'usage_status' => self::STATUSES[mb_strtolower($v['Status Penggunaan'])] ?? mb_strtolower($v['Status Penggunaan']),
                'purchase_price' => $v['Harga Perolehan (Rp)'] === '' ? null : $v['Harga Perolehan (Rp)'],
                'acquired_at' => $v['Tanggal Perolehan'] ?: null,
            ];
            $validator = Validator::make($data, [
                'asset_number' => ['nullable', 'string', 'max:100'],
                'name' => ['required', 'string', 'max:255'],
                'serial_number' => ['nullable', 'string', 'max:100'],
                'condition' => ['required', Rule::in(self::CONDITIONS)],
                'usage_status' => ['required', Rule::in(array_values(self::STATUSES))],
                'purchase_price' => ['nullable', 'regex:/^\\d{1,10}(\\.\\d{1,2})?$/'],
                'acquired_at' => ['nullable', 'date_format:Y-m-d'],
            ], [
                'required' => ':attribute wajib diisi.',
                'max' => ':attribute maksimal :max karakter.',
                'in' => ':attribute tidak sesuai pilihan template.',
                'regex' => 'Harga perolehan harus angka positif atau nol, maksimal 10 digit dan 2 desimal; tanpa Rp atau pemisah ribuan.',
                'date_format' => 'Tanggal perolehan tidak valid.',
            ], [
                'asset_number' => 'Nomor inventaris', 'name' => 'Nama barang',
                'serial_number' => 'Serial number', 'condition' => 'Kondisi',
                'usage_status' => 'Status penggunaan', 'purchase_price' => 'Harga perolehan',
            ]);
            $errors = [...$errors, ...$validator->errors()->all()];
            if ($data['asset_number'] !== '' && ($numbers[mb_strtolower($data['asset_number'])] ?? 0) > 1) {
                $errors[] = 'Nomor inventaris berulang dalam file.';
            }
            if (isset($existing[mb_strtolower($data['asset_number'])])) {
                $errors[] = 'Nomor inventaris sudah ada di database atau pernah digunakan untuk aset lain.';
            }
            $warnings = [];
            foreach (['category' => ['Kategori', 'asset_category_id'], 'location' => ['Cabang / Lokasi', 'location_id'], 'room' => ['Ruangan', 'room_id']] as $type => [$header, $field]) {
                $value = $v[$header];
                if ($value === '') {
                    $data[$field] = null;
                    if ($type !== 'room') {
                        $errors[] = $header.' wajib diisi.';
                    }

                    continue;
                }
                $key = self::mappingKey($type, $value, $type === 'room' ? $v['Cabang / Lokasi'] : '');
                $options = $masters[$type];
                if ($type === 'room') {
                    $options = $options->where('location_id', $data['location_id']);
                }
                $matches = $options->filter(fn ($master): bool => in_array(mb_strtolower($value), [mb_strtolower($master->name), mb_strtolower($master->code)], true));
                $selected = array_key_exists($key, $mapping) ? $options->firstWhere('id', $mapping[$key]) : ($matches->count() === 1 ? $matches->first() : null);
                $data[$field] = $selected?->id;
                $groups[$key] = ['key' => $key, 'type' => $type, 'label' => $header, 'value' => $value, 'location' => $type === 'room' ? $v['Cabang / Lokasi'] : '', 'selected' => $selected?->id, 'options' => $options];
                if (! $selected) {
                    $warnings[] = $header.' “'.$value.'” perlu dipetakan ke master yang valid'.($type === 'room' ? ' pada lokasi terpilih' : '').'.';
                }
            }
            $code = $v['Kode Barang'];
            $catalog = null;
            if ($code !== '') {
                $key = self::mappingKey('catalog', $code);
                $catalog = array_key_exists($key, $mapping) ? $catalogItems->firstWhere('id', $mapping[$key]) : $catalogItems->first(fn ($item): bool => mb_strtolower($item->code) === mb_strtolower($code));
                $groups[$key] = ['key' => $key, 'type' => 'catalog', 'label' => 'Kode Barang', 'value' => $code, 'location' => '', 'selected' => $catalog?->id, 'options' => $catalogItems];
                if (! $catalog) {
                    $warnings[] = 'Kode barang “'.$code.'” belum tersedia. Buat atau petakan master barang.';
                }
            } else {
                $matches = $catalogItems->filter(fn ($item): bool => mb_strtolower($item->name) === mb_strtolower($data['name']) && $item->asset_category_id === $data['asset_category_id']);
                $catalog = $matches->count() === 1 ? $matches->first() : null;
            }
            $data['catalog_item_id'] = $catalog?->id;
            if ($catalog) {
                if ($catalog->asset_category_id !== $data['asset_category_id']) {
                    $errors[] = 'Kategori harus sesuai master barang yang dipilih.';
                }
                $data['name'] = $catalog->name;
            }
            $description = [];
            foreach (['Kode Barang', 'Merk', 'Tipe / Model', 'Spesifikasi', 'Satuan', 'Unit Kerja', 'Penanggung Jawab', 'Sumber Perolehan', 'Vendor / Pemberi', 'Nomor Dokumen', 'Tanggal Pemeriksaan', 'Tautan Foto / Dokumen', 'Keterangan'] as $header) {
                if ($v[$header] !== '') {
                    $description[] = $header.': '.$v[$header];
                }
            }
            $data['description'] = $description === [] ? null : implode("\n", $description);
            if (strlen($data['description'] ?? '') > 60000) {
                $errors[] = 'Kolom tambahan terlalu panjang (maksimal 60.000 byte per aset).';
            }
            $prepared[] = [...$row, 'data' => $data, 'errors' => $errors, 'warnings' => $warnings];
        }

        return ['rows' => $prepared, 'groups' => array_values($groups), 'invalid' => count(array_filter($prepared, fn (array $row): bool => $row['errors'] !== [])), 'unmapped' => count(array_filter($prepared, fn (array $row): bool => $row['warnings'] !== []))];
    }

    public function confirm(array $rows, array $mapping): int
    {
        abort_unless(auth()->user()?->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]), 403);

        return DB::transaction(function () use ($rows, $mapping): int {
            $preview = $this->preview($rows, $mapping);
            $errors = [];
            foreach ($preview['rows'] as $row) {
                foreach ([...$row['errors'], ...$row['warnings']] as $message) {
                    $errors[] = 'Baris '.$row['number'].': '.$message;
                }
            }
            if ($errors !== []) {
                throw ValidationException::withMessages(['import' => $errors]);
            }
            foreach ($preview['rows'] as $row) {
                Asset::create(array_replace($row['data'], ['activation_stage' => 'registered', 'verification_confirmed_at' => now(), 'verification_confirmed_by' => auth()->id()]));
            }

            return count($preview['rows']);
        });
    }
}
