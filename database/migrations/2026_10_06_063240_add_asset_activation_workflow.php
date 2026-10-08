<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->string('workflow_stage', 30)->default('legacy');
            $table->timestamp('inspected_at')->nullable();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('inspection_notes')->nullable();
            $table->timestamp('documents_verified_at')->nullable();
            $table->foreignId('documents_verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('document_reference')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->restrictOnDelete();
        });
        Schema::table('assets', function (Blueprint $table): void {
            $table->foreignId('goods_receipt_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('receipt_unit_index')->nullable();
            $table->unique(['goods_receipt_item_id', 'receipt_unit_index'], 'assets_receipt_unit_unique');
            $table->string('activation_stage', 30)->default('active')->index();
            $table->timestamp('verification_confirmed_at')->nullable();
            $table->foreignId('verification_confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('label_printed_at')->nullable();
            $table->timestamp('label_attached_at')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('handed_over_at')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('handover_reference')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropUnique('assets_receipt_unit_unique');
            $table->dropConstrainedForeignId('goods_receipt_item_id');
            $table->dropConstrainedForeignId('verification_confirmed_by');
            $table->dropColumn(['receipt_unit_index', 'activation_stage', 'verification_confirmed_at', 'label_printed_at', 'label_attached_at', 'placed_at', 'handed_over_at', 'recipient_name', 'handover_reference']);
        });
        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('inspected_by');
            $table->dropConstrainedForeignId('documents_verified_by');
            $table->dropConstrainedForeignId('registered_by');
            $table->dropColumn(['workflow_stage', 'inspected_at', 'inspection_notes', 'documents_verified_at', 'document_reference', 'registered_at']);
        });
    }
};
