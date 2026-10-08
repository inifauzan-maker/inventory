<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class PreviewAssetImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]) ?? false;
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'extensions:xlsx', 'mimes:xlsx', 'max:5120']];
    }

    public function messages(): array
    {
        return ['file.required' => 'Pilih file Excel inventaris.', 'file.file' => 'Unggahan tidak valid.', 'file.extensions' => 'Gunakan file berakhiran .xlsx.', 'file.mimes' => 'File harus berupa workbook XLSX.', 'file.max' => 'Ukuran file maksimal 5 MB.'];
    }
}
