<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QA owns testing and QA approval; Developer owns fixing bugs; Tech Lead
 * owns technical execution — none of that has anywhere to live without a
 * real bug record. No Bug/issue-tracking table existed anywhere in this
 * schema before this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('steps_to_reproduce')->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->foreignId('reported_by')->nullable()->constrained('staff')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('staff')->onDelete('set null');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            // open -> in_progress -> fixed -> qa_retest -> verified -> closed
            // (or) qa_retest -> failed -> back to in_progress
            $table->enum('status', ['open', 'in_progress', 'fixed', 'qa_retest', 'failed', 'verified', 'closed'])->default('open');
            $table->foreignId('verified_by')->nullable()->constrained('staff')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'status']);
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bugs');
    }
};
