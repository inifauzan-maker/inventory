<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proposal = $this->route('procurementRequest');

        return $proposal ? ($this->user()?->can('update', $proposal) ?? false) : $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'submission_key' => $this->route('procurementRequest') ? ['nullable'] : ['required', 'uuid'],
            'version' => $this->route('procurementRequest') ? ['required', 'integer', 'min:1'] : ['nullable'],
            'division' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'request_date' => ['required', 'date_format:Y-m-d'],
            'purpose' => ['required', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'selection_mode' => ['required', Rule::in(['all', 'alternatives'])],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.category' => ['required', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.specification' => ['nullable', 'string', 'max:2000'],
            'items.*.estimated_unit_price' => ['nullable', 'regex:/^\\d{1,10}(\\.\\d{1,2})?$/'],
        ];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'string' => ':attribute harus berupa teks.', 'max' => ':attribute melebihi batas :max.', 'min' => ':attribute minimal :min.', 'integer' => ':attribute harus bilangan bulat.', 'uuid' => 'Kunci pengajuan tidak valid. Buka ulang formulir.', 'date_format' => 'Tanggal harus valid.', 'in' => 'Jenis pengajuan tidak valid.', 'regex' => 'Harga harus angka positif atau nol, maksimal 10 digit dan 2 desimal tanpa pemisah ribuan.'];
    }

    public function attributes(): array
    {
        return ['division' => 'Divisi / unit kerja', 'subject' => 'Perihal', 'purpose' => 'Alasan pengajuan', 'request_date' => 'Tanggal', 'items' => 'Daftar barang', 'items.*.name' => 'Nama barang', 'items.*.category' => 'Kategori', 'items.*.quantity' => 'Jumlah', 'items.*.unit' => 'Satuan'];
    }
}
