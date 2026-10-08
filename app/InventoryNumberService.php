<?php

namespace App;

use App\Models\Asset;
use App\Models\CatalogItem;
use App\Models\InventoryNumberSetting;
use Illuminate\Support\Facades\DB;

class InventoryNumberService
{
    public function nextAssetNumber(?string $itemCode = null): string
    {
        return DB::transaction(function () use ($itemCode): string {
            $settings = InventoryNumberSetting::whereKey(1)->lockForUpdate()->firstOrFail();
            $prefix = $settings->prefix;
            if ($settings->include_item_code && $itemCode !== null) {
                $prefix .= '-'.$itemCode;
            }
            $prefix .= '-'.now()->format('Y');

            return $this->allocate('asset:'.$prefix, $prefix, fn (string $number): bool => DB::table('issued_inventory_numbers')->where('asset_number', $number)->exists() || Asset::where('asset_number', $number)->exists());
        });
    }

    public function nextItemCode(): string
    {
        return DB::transaction(fn (): string => $this->allocate('catalog:BRG', 'BRG', fn (string $code): bool => CatalogItem::where('code', $code)->exists()));
    }

    private function allocate(string $scope, string $prefix, \Closure $exists): string
    {
        DB::table('inventory_number_sequences')->insertOrIgnore(['scope' => $scope, 'last_number' => 0]);
        $sequence = DB::table('inventory_number_sequences')->where('scope', $scope)->lockForUpdate()->first();
        $counter = (int) $sequence->last_number;
        do {
            $counter++;
            $number = $prefix.'-'.str_pad((string) $counter, 6, '0', STR_PAD_LEFT);
        } while ($exists($number));
        DB::table('inventory_number_sequences')->where('scope', $scope)->update(['last_number' => $counter]);

        return $number;
    }
}
