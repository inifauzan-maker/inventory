<?php

namespace App;

use App\Models\ProcurementRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data): PurchaseOrder {
            $proposal = null;
            $approvedItems = collect();
            if (! empty($data['procurement_request_id'])) {
                $proposal = ProcurementRequest::whereKey($data['procurement_request_id'])->lockForUpdate()->firstOrFail();
                if ($proposal->status !== 'approved') {
                    throw ValidationException::withMessages(['procurement_request_id' => 'Pengajuan harus disetujui Direksi.']);
                }
                $approvedItems = $proposal->orderableItems()->keyBy('id');
                $requested = [];
                foreach ($data['items'] as $index => $item) {
                    $source = $approvedItems->get($item['procurement_request_item_id'] ?? null);
                    if (! $source) {
                        throw ValidationException::withMessages(["items.$index.procurement_request_item_id" => 'Barang bukan bagian pengajuan atau bukan alternatif yang disetujui.']);
                    }
                    $requested[$source->id] = ($requested[$source->id] ?? 0) + $item['quantity'];
                    $ordered = PurchaseOrderItem::where('procurement_request_item_id', $source->id)->whereHas('purchaseOrder', fn ($query) => $query->where('status', '!=', 'rejected'))->sum('quantity');
                    if ($ordered + $requested[$source->id] > $source->quantity) {
                        throw ValidationException::withMessages(["items.$index.quantity" => 'Jumlah PO melebihi sisa jumlah yang disetujui.']);
                    }
                }
            } elseif (collect($data['items'])->contains(fn ($item) => ! empty($item['procurement_request_item_id']))) {
                throw ValidationException::withMessages(['procurement_request_id' => 'Pengajuan sumber wajib dipilih.']);
            }
            $order = PurchaseOrder::create([
                'procurement_request_id' => $proposal?->id,
                'procurement_request_version' => $proposal?->version,
                'order_number' => $data['order_number'],
                'vendor_name' => $data['vendor_name'],
                'order_date' => $data['order_date'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);
            $total = '0.00';
            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'procurement_request_item_id' => $proposal ? $item['procurement_request_item_id'] : null,
                    'item_name' => $proposal ? $approvedItems[$item['procurement_request_item_id']]->name : $item['item_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'received_quantity' => 0,
                    'notes' => $item['notes'] ?? null,
                ]);
                $total = bcadd($total, bcmul((string) $item['unit_price'], (string) $item['quantity'], 2), 2);
            }
            $order->update(['total_amount' => $total]);

            return $order;
        });
    }
}
