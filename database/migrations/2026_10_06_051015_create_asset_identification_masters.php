<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->foreignId('asset_category_id')->constrained()->restrictOnDelete();
            $table->string('unit', 50)->default('Unit');
            $table->text('specification')->nullable();
            $table->timestamps();
        });
        Schema::create('inventory_number_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('prefix', 20)->default('INV');
            $table->boolean('include_item_code')->default(false);
            $table->timestamps();
        });
        DB::table('inventory_number_settings')->insert(['id' => 1, 'prefix' => 'INV', 'include_item_code' => false, 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('inventory_number_sequences', function (Blueprint $table): void {
            $table->string('scope', 120)->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
        Schema::table('assets', function (Blueprint $table): void {
            $table->foreignId('catalog_item_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('catalog_item_id');
        });
        Schema::dropIfExists('inventory_number_sequences');
        Schema::dropIfExists('inventory_number_settings');
        Schema::dropIfExists('catalog_items');
    }
};
