<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issued_inventory_numbers', function (Blueprint $table): void {
            $table->string('asset_number', 100)->primary();
            $table->unsignedBigInteger('original_asset_id')->nullable();
            $table->timestamp('issued_at');
        });
        DB::table('assets')->orderBy('id')->chunkById(500, function ($assets): void {
            foreach ($assets as $asset) {
                DB::table('issued_inventory_numbers')->insert([
                    'asset_number' => $asset->asset_number,
                    'original_asset_id' => $asset->id,
                    'issued_at' => $asset->created_at ?? now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_inventory_numbers');
    }
};
