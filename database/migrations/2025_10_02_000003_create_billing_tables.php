<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('municipal_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('issue_date')->index();
            $table->date('due_date')->index();
            $table->bigInteger('previous_balance_cents')->default(0);
            $table->bigInteger('charges_cents')->default(0);
            $table->bigInteger('adjustments_cents')->default(0);
            $table->bigInteger('payments_cents')->default(0);
            $table->bigInteger('arrears_cents')->default(0);
            $table->bigInteger('total_cents')->default(0); // amount due on this bill
            $table->bigInteger('amount_paid_cents')->default(0);
            $table->string('status')->default('draft')->index(); // draft|issued|partially_paid|paid|overdue|disputed|cancelled
            $table->timestamps();
            $table->softDeletes();
            $table->index(['municipal_account_id', 'status']);
        });

        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->string('charge_type')->default('other'); // rates|water|refuse|sewer|other|adjustment
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->bigInteger('unit_price_cents')->default(0);
            $table->bigInteger('amount_cents');
            $table->foreignId('meter_reading_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('disconnection_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipal_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('arrears_cents')->default(0);
            $table->string('status')->default('warning')->index();
            // warning|notice_issued|scheduled|disconnected|restoration_pending|restored|cancelled
            $table->date('notice_date')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disconnection_notices');
        Schema::dropIfExists('bill_items');
        Schema::dropIfExists('bills');
    }
};
