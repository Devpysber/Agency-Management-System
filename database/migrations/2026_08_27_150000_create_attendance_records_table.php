<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->string('person_type', 12);          // staff | client
            $table->unsignedBigInteger('person_id');     // staff.id  or  users.id (client)
            $table->string('date', 10);                  // 'Y-m-d' — stored as plain string on purpose
            $table->string('status', 16)->default('present'); // present|absent|late|half_day|leave|remote|holiday
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->unsignedInteger('worked_minutes')->nullable();
            $table->string('source', 10)->default('manual'); // manual | auto
            $table->string('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['person_type', 'person_id', 'date']);
            $table->index(['person_type', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
