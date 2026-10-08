<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementDecision extends Model
{
    use HasFactory;

    protected $fillable = ['procurement_request_id', 'reviewer_id', 'reviewer_name', 'level', 'decision', 'version', 'notes', 'selected_item_id', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime', 'version' => 'integer'];

    public function selectedItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestItem::class, 'selected_item_id');
    }

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
