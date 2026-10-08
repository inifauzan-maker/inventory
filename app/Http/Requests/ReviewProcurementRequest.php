<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('level') === 'finance' ? 'reviewFinance' : 'reviewDirector';

        return $this->user()?->can($ability, $this->route('procurementRequest')) ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'notes' => ['required_if:decision,rejected', 'nullable', 'string', 'max:5000'],
            'selected_item_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['decision.required' => 'Pilih keputusan.', 'decision.in' => 'Keputusan tidak valid.', 'notes.required_if' => 'Alasan penolakan wajib diisi.', 'notes.max' => 'Catatan maksimal 5.000 karakter.', 'selected_item_id.integer' => 'Pilih alternatif yang valid.'];
    }
}
