<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]) ?? false;
    }

    public function rules(): array
    {
        return ['code' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:catalog_items,code'], 'name' => ['required', 'string', 'max:255'], 'asset_category_id' => ['required', 'exists:asset_categories,id'], 'unit' => ['required', 'string', 'max:50'], 'specification' => ['nullable', 'string', 'max:5000']];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'code.unique' => 'Kode barang sudah digunakan.', 'code.regex' => 'Kode barang hanya boleh huruf, angka, garis bawah, dan tanda hubung.', 'exists' => 'Master yang dipilih tidak ditemukan.', 'max' => ':attribute melebihi batas :max karakter.'];
    }
}
