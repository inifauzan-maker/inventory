<?php

namespace App;

use App\Models\Asset;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptWorkflowService
{
    public function receive(array $data): GoodsReceipt
    {
        return DB::transaction(function () use ($data): GoodsReceipt {
            $po = PurchaseOrder::whereKey($data['purchase_order_id'])->lockForUpdate()->firstOrFail();
            if ($po->status !== 'approved') {
                throw ValidationException::withMessages(['purchase_order_id' => 'Penerimaan hanya dapat dicatat untuk PO yang disetujui.']);
            }
            $receipt = GoodsReceipt::create(['purchase_order_id' => $po->id, 'receipt_number' => $data['receipt_number'], 'received_at' => $data['received_at'], 'status' => 'received', 'workflow_stage' => 'received', 'notes' => $data['notes'] ?? null]);
            foreach ($data['items'] as $itemData) {
                $item = PurchaseOrderItem::whereKey($itemData['purchase_order_item_id'])->lockForUpdate()->firstOrFail();
                if ($item->purchase_order_id !== $po->id || $item->received_quantity + $itemData['quantity_received'] > $item->quantity) {
                    throw ValidationException::withMessages(['items' => 'Item atau jumlah penerimaan tidak sesuai sisa PO.']);
                }
                GoodsReceiptItem::create(array_replace(array_intersect_key($itemData, array_flip(['quantity_received', 'asset_number', 'asset_name', 'asset_category_id', 'location_id', 'condition', 'usage_status'])), ['goods_receipt_id' => $receipt->id, 'purchase_order_item_id' => $item->id, 'item_name' => $item->item_name, 'asset_name' => $itemData['asset_name'] ?? $item->item_name, 'condition' => $itemData['condition'] ?? 'baik', 'usage_status' => $itemData['usage_status'] ?? 'dipakai']));
                $item->increment('received_quantity', $itemData['quantity_received']);
            }

            return $receipt;
        });
    }

    public function advance(GoodsReceipt $receipt, User $user, string $step, array $data): void
    {
        abort_unless($user->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]), 403);
        DB::transaction(function () use ($receipt, $user, $step, $data): void {
            $receipt = GoodsReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $expected = ['inspect' => 'received', 'documents' => 'inspected', 'register' => 'documents_verified'];
            if (! isset($expected[$step]) || $receipt->workflow_stage !== $expected[$step]) {
                throw ValidationException::withMessages(['stage' => 'Tahap sebelumnya belum selesai atau tahap ini sudah dicatat.']);
            }
            if ($step === 'inspect') {
                $receipt->update(['workflow_stage' => 'inspected', 'inspected_at' => now(), 'inspected_by' => $user->id, 'inspection_notes' => $data['notes'] ?? null]);
            } elseif ($step === 'documents') {
                $receipt->update(['workflow_stage' => 'documents_verified', 'documents_verified_at' => now(), 'documents_verified_by' => $user->id, 'document_reference' => $data['document_reference']]);
            } else {
                foreach ($data['items'] ?? [] as $values) {
                    $detail = $receipt->items()->whereKey($values['id'])->first();
                    if (! $detail) {
                        throw ValidationException::withMessages(['items' => 'Item inventaris bukan bagian penerimaan ini.']);
                    }
                    $detail->update(array_intersect_key($values, array_flip(['asset_category_id', 'location_id'])));
                }
                foreach ($receipt->items()->with('purchaseOrderItem')->lockForUpdate()->get() as $detail) {
                    if (! $detail->asset_category_id || ! $detail->location_id) {
                        throw ValidationException::withMessages(['items' => 'Kategori dan lokasi pencatatan wajib dipilih untuk '.$detail->item_name.'.']);
                    }
                    if ($detail->quantity_received > 1 && $detail->asset_number) {
                        throw ValidationException::withMessages(['items' => 'Untuk penerimaan lebih dari satu unit, kosongkan nomor manual dan gunakan nomor otomatis.']);
                    }
                    for ($unit = 1; $unit <= $detail->quantity_received; $unit++) {
                        Asset::create(['asset_number' => $detail->asset_number, 'asset_category_id' => $detail->asset_category_id, 'name' => $detail->asset_name ?: $detail->item_name, 'location_id' => $detail->location_id, 'condition' => $detail->condition ?: 'baik', 'usage_status' => $detail->usage_status ?: 'dipakai', 'purchase_price' => $detail->purchaseOrderItem->unit_price, 'acquired_at' => $receipt->received_at, 'description' => 'Dari penerimaan '.$receipt->receipt_number, 'goods_receipt_item_id' => $detail->id, 'receipt_unit_index' => $unit, 'activation_stage' => 'registered', 'verification_confirmed_at' => $receipt->documents_verified_at, 'verification_confirmed_by' => $receipt->documents_verified_by]);
                    }
                }
                $receipt->update(['workflow_stage' => 'registered', 'registered_at' => now(), 'registered_by' => $user->id]);
            }
        });
    }
}
