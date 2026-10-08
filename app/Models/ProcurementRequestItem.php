<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementRequestItem extends Model
{
    use HasFactory;

    protected $fillable = ['procurement_request_id', 'name', 'category', 'quantity', 'unit', 'specification', 'estimated_unit_price', 'subtotal'];

    protected $casts = ['quantity' => 'integer', 'estimated_unit_price' => 'decimal:2', 'subtotal' => 'decimal:2'];

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }
}
