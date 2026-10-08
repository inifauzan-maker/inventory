<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementRequest extends Model
{
    use HasFactory;

    protected $fillable = ['submission_key', 'letter_number', 'requester_id', 'requester_name', 'division', 'subject', 'request_date', 'purpose', 'notes', 'selection_mode', 'status', 'version', 'estimated_total', 'submitted_at'];

    protected $casts = ['request_date' => 'date', 'submitted_at' => 'datetime', 'estimated_total' => 'decimal:2', 'version' => 'integer'];

    public static function statusLabels(): array
    {
        return ['draft' => 'Draf', 'pending_finance' => 'Menunggu Keuangan', 'pending_director' => 'Menunggu Direksi', 'approved' => 'Disetujui Direksi', 'rejected' => 'Ditolak'];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementRequestItem::class)->orderBy('id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ProcurementDecision::class)->orderBy('id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($user): void {
            $scope->where('requester_id', $user->id);
            if ($user->hasRole(User::ROLE_OPERASIONAL)) {
                $scope->orWhere('status', 'approved');
            } elseif ($user->hasRole(User::ROLE_KEUANGAN)) {
                $scope->orWhere('status', '!=', 'draft');
            } elseif ($user->hasRole(User::ROLE_DIREKSI)) {
                $scope->orWhereHas('decisions', fn (Builder $decisions) => $decisions->where('level', 'finance')->where('decision', 'approved'));
            }
        });
    }

    public function canBeReviewedBy(User $user, string $level): bool
    {
        $role = $level === 'finance' ? User::ROLE_KEUANGAN : User::ROLE_DIREKSI;
        $status = $level === 'finance' ? 'pending_finance' : 'pending_director';

        return in_array($level, ['finance', 'director'], true)
            && $this->status === $status
            && $this->requester_id !== $user->id
            && $user->hasAnyRole([$role, User::ROLE_SUPERADMIN])
            && ! $this->decisions()->where('reviewer_id', $user->id)->exists();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function orderableItems(): Collection
    {
        $items = $this->items()->get();
        if ($this->selection_mode === 'alternatives') {
            $selectedId = $this->decisions()->where('level', 'finance')->where('decision', 'approved')->where('version', $this->version)->latest('id')->value('selected_item_id');
            $items = $items->where('id', $selectedId);
        }

        return $items->values();
    }
}
