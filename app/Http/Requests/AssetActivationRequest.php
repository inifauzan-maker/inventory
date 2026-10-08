<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetActivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]) ?? false;
    }

    public function rules(): array
    {
        $step = $this->route('step');

        return [
            'confirmed' => ['accepted'],
            'location_id' => $step === 'place' ? ['required', 'integer', 'exists:locations,id'] : ['nullable'],
            'room_id' => $step === 'place' ? ['nullable', 'integer', Rule::exists('rooms', 'id')->where('location_id', $this->input('location_id'))] : ['nullable'],
            'recipient_name' => $step === 'handover' ? ['required', 'string', 'max:255'] : ['nullable'],
            'handover_reference' => $step === 'handover' ? ['required', 'string', 'max:255'] : ['nullable'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return ['confirmed.accepted' => 'Konfirmasi bahwa tahap ini benar-benar selesai.', 'recipient_name.required' => 'Nama penerima wajib diisi.', 'handover_reference.required' => 'Nomor berita acara / referensi serah terima wajib diisi.', 'location_id.required' => 'Pilih lokasi penempatan.', 'room_id.exists' => 'Ruangan harus sesuai lokasi.'];
    }
}
