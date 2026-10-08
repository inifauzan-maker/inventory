<?php

namespace App\Models;

use App\InventoryNumberService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Asset extends Model
{
    protected $fillable = [
        'goods_receipt_item_id', 'receipt_unit_index', 'activation_stage', 'verification_confirmed_at', 'verification_confirmed_by', 'label_printed_at', 'label_attached_at', 'placed_at', 'handed_over_at', 'recipient_name', 'handover_reference',
        'catalog_item_id',
        'asset_number',
        'asset_category_id',
        'name',
        'description',
        'location_id',
        'room_id',
        'condition',
        'usage_status',
        'purchase_price',
        'acquired_at',
        'serial_number',
    ];

    protected $casts = [
        'verification_confirmed_at' => 'datetime', 'label_printed_at' => 'datetime', 'label_attached_at' => 'datetime', 'placed_at' => 'datetime', 'handed_over_at' => 'datetime',
        'purchase_price' => 'decimal:2',
        'acquired_at' => 'date',
    ];

    protected $attributes = ['activation_stage' => 'active'];

    protected static function booted(): void
    {
        static::saving(function (self $asset): void {
            if ($asset->catalog_item_id) {
                $item = CatalogItem::findOrFail($asset->catalog_item_id);
                $asset->name = $item->name;
                $asset->asset_category_id = $item->asset_category_id;
            }
        });
        static::created(function (self $asset) {
            $asset->activities()->create([
                'user_id' => auth()->id(),
                'activity_type' => 'asset_created',
                'message' => 'Aset ditambahkan ke inventaris',
                'details' => 'Nomor aset: '.$asset->asset_number,
                'occurred_at' => now(),
            ]);
        });

        static::updated(function (self $asset) {
            $original = $asset->getOriginal();
            $changes = [];

            if (($original['condition'] ?? null) !== $asset->condition) {
                $changes[] = 'kondisi '.($original['condition'] ?? '-').' → '.$asset->condition;
            }

            if (($original['usage_status'] ?? null) !== $asset->usage_status) {
                $changes[] = 'status penggunaan '.($original['usage_status'] ?? '-').' → '.$asset->usage_status;
            }

            if (! empty($changes)) {
                $asset->activities()->create([
                    'user_id' => auth()->id(),
                    'activity_type' => 'status_change',
                    'message' => 'Status aset diubah',
                    'details' => implode('; ', $changes),
                    'occurred_at' => now(),
                ]);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function save(array $options = []): bool
    {
        return DB::transaction(function () use ($options): bool {
            $creating = ! $this->exists;
            if (! $creating && $this->isDirty('asset_number')) {
                throw ValidationException::withMessages(['asset_number' => 'Nomor inventaris tidak dapat diubah setelah diterbitkan.']);
            }
            if ($creating) {
                if ($this->activation_stage === 'registered' && (! $this->verification_confirmed_at || ! $this->verification_confirmed_by)) {
                    throw ValidationException::withMessages(['verification' => 'Pemeriksaan barang dan verifikasi dokumen harus selesai sebelum nomor diterbitkan.']);
                }
                if ($this->asset_number === null || $this->asset_number === '') {
                    $this->asset_number = app(InventoryNumberService::class)->nextAssetNumber($this->catalogItem?->code);
                }
                try {
                    DB::table('issued_inventory_numbers')->insert(['asset_number' => $this->asset_number, 'original_asset_id' => null, 'issued_at' => now()]);
                } catch (UniqueConstraintViolationException) {
                    throw ValidationException::withMessages(['asset_number' => 'Nomor inventaris pernah digunakan dan tidak boleh digunakan kembali untuk aset berbeda.']);
                }
            }
            $saved = parent::save($options);
            if ($creating && $saved) {
                DB::table('issued_inventory_numbers')->where('asset_number', $this->asset_number)->update(['original_asset_id' => $this->id]);
            }

            return $saved;
        });
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function mutations(): HasMany
    {
        return $this->hasMany(AssetMutation::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AssetActivity::class)->orderByDesc('occurred_at');
    }
}
