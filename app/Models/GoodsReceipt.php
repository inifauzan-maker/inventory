<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'workflow_stage', 'inspected_at', 'inspected_by', 'inspection_notes', 'documents_verified_at', 'documents_verified_by', 'document_reference', 'registered_at', 'registered_by',
        'purchase_order_id',
        'receipt_number',
        'received_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'inspected_at' => 'datetime', 'documents_verified_at' => 'datetime', 'registered_at' => 'datetime',
        'received_at' => 'date',
    ];

    public static function workflowLabels(): array
    {
        return ['legacy' => 'Penerimaan lama', 'received' => 'Barang diterima — menunggu pemeriksaan', 'inspected' => 'Barang diperiksa — menunggu verifikasi dokumen', 'documents_verified' => 'Dokumen diverifikasi — siap input inventaris', 'registered' => 'Inventaris dicatat dan nomor diterbitkan'];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
