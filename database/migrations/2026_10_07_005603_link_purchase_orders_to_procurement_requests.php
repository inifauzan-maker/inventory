<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->foreignId('procurement_request_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('procurement_request_version')->nullable();
        });
        Schema::table('purchase_order_items', function (Blueprint $table): void {
            $table->foreignId('procurement_request_item_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('procurement_request_item_id'));
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('procurement_request_id');
            $table->dropColumn('procurement_request_version');
        });
    }
};
