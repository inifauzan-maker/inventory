<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('submission_key')->unique();
            $table->string('letter_number', 100)->unique();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->string('requester_name');
            $table->string('division', 150);
            $table->string('subject');
            $table->date('request_date');
            $table->text('purpose');
            $table->text('notes')->nullable();
            $table->string('selection_mode', 20);
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->decimal('estimated_total', 18, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['requester_id', 'created_at']);
        });
        Schema::create('procurement_request_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('category', 100);
            $table->unsignedInteger('quantity');
            $table->string('unit', 50);
            $table->text('specification')->nullable();
            $table->decimal('estimated_unit_price', 12, 2)->nullable();
            $table->decimal('subtotal', 18, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('procurement_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained()->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('reviewer_name');
            $table->string('level', 20);
            $table->string('decision', 20);
            $table->unsignedInteger('version');
            $table->text('notes')->nullable();
            $table->foreignId('selected_item_id')->nullable()->constrained('procurement_request_items')->restrictOnDelete();
            $table->timestamp('decided_at');
            $table->timestamps();
            $table->unique(['procurement_request_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_decisions');
        Schema::dropIfExists('procurement_request_items');
        Schema::dropIfExists('procurement_requests');
    }
};
