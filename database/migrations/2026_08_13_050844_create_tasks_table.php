<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();
            
            // Relationships
            $table->foreignId('assigned_to')->nullable()->constrained('staff')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('related_to')->nullable();
            $table->string('related_type')->nullable()->comment('deal, contact, company');
            
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('priority');
            $table->index('due_date');
            $table->index('assigned_to');
            $table->index(['related_to', 'related_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};