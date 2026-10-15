<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_interruptions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type')->default('emergency'); // planned|emergency|maintenance
            $table->string('service')->default('water')->index(); // water|refuse|roads|other
            $table->string('area')->index();
            $table->string('suburb')->nullable()->index();
            $table->string('ward')->nullable()->index();
            $table->timestamp('starts_at');
            $table->timestamp('expected_restoration_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('reason')->nullable();
            $table->string('status')->default('planned')->index(); // planned|active|restoring|resolved
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('water_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('area')->index();
            $table->string('suburb')->nullable();
            $table->string('ward')->nullable();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday
            $table->time('start_time');
            $table->time('end_time');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status')->default('active'); // active|suspended
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('refuse_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('area')->index();
            $table->string('suburb')->nullable();
            $table->string('ward')->nullable();
            $table->unsignedTinyInteger('collection_day'); // 0=Sunday
            $table->string('frequency')->default('weekly'); // weekly|fortnightly
            $table->date('next_collection_date')->nullable();
            $table->string('status')->default('active'); // active|suspended|delayed
            $table->text('notes')->nullable(); // holiday changes etc
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('body');
            $table->string('category')->default('general')->index();
            // general|water|refuse|roads|public_health|emergency|events|community|billing|service_interruption
            $table->string('priority')->default('normal')->index(); // low|normal|high|urgent
            $table->foreignId('image_attachment_id')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('draft')->index(); // draft|scheduled|published|expired
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('announcement_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->string('ward')->nullable();
            $table->string('suburb')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_targets');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('refuse_schedules');
        Schema::dropIfExists('water_schedules');
        Schema::dropIfExists('service_interruptions');
    }
};
