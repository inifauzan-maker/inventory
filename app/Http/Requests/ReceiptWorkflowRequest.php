<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ReceiptWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]) ?? false;
    }

    public function rules(): array
    {
        return ['confirmed' => ['accepted'], 'notes' => ['nullable', 'string', 'max:5000'], 'document_reference' => $this->route('step') === 'documents' ? ['required', 'string', 'max:255'] : ['nullable'], 'items' => ['sometimes', 'array', 'max:200'], 'items.*.id' => ['required', 'integer', 'exists:goods_receipt_items,id'], 'items.*.asset_category_id' => ['nullable', 'integer', 'exists:asset_categories,id'], 'items.*.location_id' => ['nullable', 'integer', 'exists:locations,id']];
    }

    public function messages(): array
    {
        return ['confirmed.accepted' => 'Konfirmasi penyelesaian tahap terlebih dahulu.', 'document_reference.required' => 'Nomor / referensi dokumen wajib diisi.', 'exists' => 'Data yang dipilih tidak valid.'];
    }
}
