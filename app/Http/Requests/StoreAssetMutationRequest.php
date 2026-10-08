<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssetMutationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]) ?? false;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'from_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'from_room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'to_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'to_room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
