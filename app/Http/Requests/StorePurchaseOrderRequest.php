<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]) ?? false;
    }

    public function rules(): array
    {
        return [
            'procurement_request_id' => ['nullable', 'integer', 'exists:procurement_requests,id'],
            'items.*.procurement_request_item_id' => ['required_with:procurement_request_id', 'nullable', 'integer', 'distinct', 'exists:procurement_request_items,id'],
            'order_number' => ['required', 'string', 'max:100', 'unique:purchase_orders,order_number'],
            'vendor_name' => ['required', 'string', 'max:255'],
            'order_date' => ['required', 'date'],
            'status' => ['nullable', 'in:draft'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
