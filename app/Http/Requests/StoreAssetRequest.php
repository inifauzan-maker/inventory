<?php

namespace App\Http\Requests;

use App\Models\CatalogItem;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $id = $this->input('catalog_item_id');
        if (is_scalar($id) && $id) {
            $item = CatalogItem::find($id);
            if ($item) {
                $this->merge(['name' => $item->name, 'asset_category_id' => $item->asset_category_id]);
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL, User::ROLE_STAFF, User::ROLE_AKADEMIK]) ?? false;
    }

    public function rules(): array
    {
        $asset = $this->route('asset');

        return [
            'received_confirmed' => $asset ? ['nullable'] : ['accepted'],
            'inspection_confirmed' => $asset ? ['nullable'] : ['accepted'],
            'documents_confirmed' => $asset ? ['nullable'] : ['accepted'],
            'catalog_item_id' => ['nullable', 'integer', 'exists:catalog_items,id'],
            'asset_number' => ['nullable', 'string', 'max:100', $asset ? Rule::in([$asset->asset_number]) : Rule::unique('assets', 'asset_number'), ...($asset ? [] : [Rule::unique('issued_inventory_numbers', 'asset_number')])],
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'location_id' => ['required', 'exists:locations,id'],
            'room_id' => ['nullable', Rule::exists('rooms', 'id')->where('location_id', $this->input('location_id'))],
            'condition' => ['required', 'string', 'max:50'],
            'usage_status' => ['required', 'string', 'max:50'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'acquired_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['received_confirmed.accepted' => 'Barang harus sudah diterima.', 'inspection_confirmed.accepted' => 'Pemeriksaan barang harus selesai.', 'documents_confirmed.accepted' => 'Verifikasi dokumen harus selesai.', 'asset_number.in' => 'Nomor inventaris tidak dapat diubah setelah aset tersimpan.', 'asset_number.unique' => 'Nomor inventaris sudah digunakan.', 'catalog_item_id.exists' => 'Master barang tidak ditemukan.', 'room_id.exists' => 'Ruangan harus berada pada lokasi yang dipilih.', 'required' => ':attribute wajib diisi.'];
    }
}
