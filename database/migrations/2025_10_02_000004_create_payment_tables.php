<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // public/internal transaction reference
            $table->foreignId('municipal_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // initiator (null for imports)
            $table->bigInteger('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('provider')->index(); // mock|bank_transfer|ecocash|onemoney|card|manual|import
            $table->string('payment_method')->nullable(); // mobile_money|card|bank_transfer|cash
            $table->string('reference')->nullable()->index(); // customer-visible reference
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('provider_reference')->nullable()->index();
            $table->string('status')->default('pending')->index();
            // pending|processing|successful|failed|cancelled|refunded|unallocated
            $table->string('reconciliation_status')->default('none')->index(); // none|pending|matched|reconciled|unallocated
            $table->string('source')->default('portal'); // portal|import|manual|webhook
            $table->json('meta')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['municipal_account_id', 'status']);
        });
        // Note: provider_reference gets a dedupe check in code (mock refs are unique).

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('municipal_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('amount_cents');
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('event_id')->nullable();
            $table->string('event_type')->nullable();
            $table->string('signature')->nullable();
            $table->json('payload');
            $table->string('status')->default('received')->index(); // received|processed|failed|ignored|invalid
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id']);
        });

        Schema::create('bank_payment_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_by')->constrained('users')->cascadeOnDelete();
            $table->string('filename');
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_matched')->default(0);
            $table->unsignedInteger('rows_unmatched')->default(0);
            $table->string('status')->default('processed'); // processed|failed
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk')->default('private');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('bank_payment_imports');
        Schema::dropIfExists('payment_webhooks');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};
