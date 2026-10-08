<?php

namespace App\Http\Requests;

class ConfirmAssetImportRequest extends PreviewAssetImportRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'uuid'],
            'mapping' => ['sometimes', 'array', 'max:800'],
            'mapping.*' => ['nullable', 'integer', 'min:1'],
            'confirmed' => $this->routeIs('aset.import.mapping') ? ['nullable'] : ['accepted'],
        ];
    }

    public function messages(): array
    {
        return ['token.required' => 'Preview tidak ditemukan. Unggah ulang file.', 'token.uuid' => 'Token preview tidak valid.', 'mapping.*.integer' => 'Pilih master yang valid.', 'mapping.*.min' => 'Pilih master yang valid.', 'confirmed.accepted' => 'Centang persetujuan setelah memeriksa preview.'];
    }
}
