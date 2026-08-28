<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('shift_start', 5)->default('09:00')->after('employment_type'); // HH:MM
            $table->unsignedTinyInteger('daily_hours')->default(8)->after('shift_start');
        });

        Schema::create('attendance_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('date', 10);                     // 'Y-m-d' of the disputed day
            $table->text('message');
            $table->string('status', 12)->default('pending'); // pending | approved | rejected
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_appeals');
        Schema::table('staff', fn (Blueprint $table) => $table->dropColumn(['shift_start', 'daily_hours']));
    }
};
