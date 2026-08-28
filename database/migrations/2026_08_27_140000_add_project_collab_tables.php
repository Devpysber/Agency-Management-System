<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Deadline the admin sets for the client to submit / sign off the project.
            $table->dateTime('submission_due_at')->nullable()->after('end_date');
        });

        Schema::create('project_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'staff_id']);
        });

        Schema::create('project_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('author_role', 20)->nullable(); // admin | staff | client (snapshot)
            $table->text('body');
            $table->timestamps();
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_messages');
        Schema::dropIfExists('project_staff');
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('submission_due_at'));
    }
};
