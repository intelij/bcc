<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // e.g. PROP-000001
            $table->string('address_line');
            $table->string('suburb')->index();
            $table->string('ward')->index();
            $table->string('city')->default('Bulawayo');
            $table->string('property_type')->default('residential'); // residential|commercial|industrial|institutional
            $table->string('status')->default('pending')->index(); // pending|verified|rejected|suspended
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('property_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('relationship')->default('owner'); // owner|tenant|agent
            $table->boolean('can_view_bills')->default(true);
            $table->boolean('can_view_account')->default(true);
            $table->boolean('can_pay')->default(true);
            $table->boolean('can_submit_requests')->default(true);
            $table->boolean('can_view_statements')->default(true);
            $table->string('status')->default('pending')->index(); // pending|approved|revoked
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamps();
            $table->unique(['property_id', 'user_id', 'relationship']);
        });

        Schema::create('municipal_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number')->unique(); // e.g. BYO-100001
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('holder_name');
            $table->string('currency', 3)->default('USD');
            $table->bigInteger('balance_cents')->default(0); // positive = owed to city, negative = credit
            $table->bigInteger('arrears_cents')->default(0);
            $table->string('status')->default('active')->index(); // active|suspended|closed
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('water_meters', function (Blueprint $table) {
            $table->id();
            $table->string('meter_number')->unique();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active'); // active|faulty|replaced|removed
            $table->timestamps();
        });

        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('water_meter_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('previous_reading')->default(0);
            $table->unsignedBigInteger('current_reading');
            $table->unsignedBigInteger('consumption')->default(0); // kilolitres
            $table->date('reading_date')->index();
            $table->string('reading_type')->default('actual'); // actual|estimated|corrected
            $table->string('source')->default('manual'); // manual|resident|smart_meter|import
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
        Schema::dropIfExists('water_meters');
        Schema::dropIfExists('municipal_accounts');
        Schema::dropIfExists('property_users');
        Schema::dropIfExists('properties');
    }
};
