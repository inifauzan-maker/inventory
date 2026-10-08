<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]) ?? false;
    }

    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'receipt_number' => ['required', 'string', 'max:100', 'unique:goods_receipts,receipt_number'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'exists:purchase_order_items,id'],
            'items.*.quantity_received' => ['required', 'integer', 'min:1'],
            'items.*.asset_number' => ['nullable', 'string', 'max:100', 'distinct', Rule::unique('assets', 'asset_number'), Rule::unique('issued_inventory_numbers', 'asset_number')],
            'items.*.asset_name' => ['nullable', 'string', 'max:255'],
            'items.*.asset_category_id' => ['nullable', 'exists:asset_categories,id'],
            'items.*.location_id' => ['nullable', 'exists:locations,id'],
            'items.*.condition' => ['nullable', 'string', 'max:50'],
            'items.*.usage_status' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $purchaseOrderId = (int) ($this->input('purchase_order_id') ?? 0);
            $items = $this->input('items', []);

            if ($purchaseOrderId <= 0 || empty($items)) {
                return;
            }

            $seenAssetNumbers = [];
            $requestTotalsByItem = [];
            $itemIndicesById = [];

            foreach ($items as $index => $item) {
                $purchaseOrderItem = PurchaseOrderItem::find($item['purchase_order_item_id'] ?? null);

                if (! $purchaseOrderItem) {
                    continue;
                }

                if ((int) $purchaseOrderItem->purchase_order_id !== $purchaseOrderId) {
                    $validator->errors()->add("items.$index.purchase_order_item_id", 'Item PO tidak sesuai dengan nomor PO yang dipilih.');
                }

                $itemId = (int) $purchaseOrderItem->id;
                $quantityReceived = (int) ($item['quantity_received'] ?? 0);

                $requestTotalsByItem[$itemId] = ($requestTotalsByItem[$itemId] ?? 0) + $quantityReceived;
                $itemIndicesById[$itemId][] = $index;

                $assetNumber = trim((string) ($item['asset_number'] ?? ''));
                if ($assetNumber === '') {
                    continue;
                }

                if ($quantityReceived > 1) {
                    $validator->errors()->add("items.$index.asset_number", 'Penerimaan lebih dari satu unit harus menggunakan nomor otomatis. Kosongkan nomor manual.');
                }

                if (isset($seenAssetNumbers[$assetNumber])) {
                    $validator->errors()->add("items.$index.asset_number", 'Nomor aset tidak boleh duplikat dalam satu penerimaan.');

                    continue;
                }

                $seenAssetNumbers[$assetNumber] = true;

                if (Asset::where('asset_number', $assetNumber)->exists()) {
                    $validator->errors()->add("items.$index.asset_number", 'Nomor aset sudah digunakan pada inventaris lain.');
                }
            }

            foreach ($items as $index => $item) {
                $purchaseOrderItem = PurchaseOrderItem::find($item['purchase_order_item_id'] ?? null);

                if (! $purchaseOrderItem) {
                    continue;
                }

                $itemId = (int) $purchaseOrderItem->id;
                $remainingQuantity = (int) $purchaseOrderItem->quantity - (int) $purchaseOrderItem->received_quantity;
                $totalRequested = $requestTotalsByItem[$itemId] ?? 0;

                if ($totalRequested > $remainingQuantity) {
                    foreach ($itemIndicesById[$itemId] ?? [] as $itemIndex) {
                        $validator->errors()->add("items.$itemIndex.quantity_received", 'Jumlah diterima melebihi sisa kuantitas PO.');
                    }
                }
            }
        });
    }
}
