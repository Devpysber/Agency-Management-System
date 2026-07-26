<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('deal_name');
            $table->text('deal_notes')->nullable();
            
            // Financial
            $table->decimal('deal_value', 15, 2);
            $table->string('currency')->default('USD');
            
            // Timeline
            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            $table->string('deal_stage')->default('lead');
            $table->integer('probability')->default(0);
            
            // Status
            $table->string('deal_status')->default('active');
            
            // Relationships
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('deal_stage');
            $table->index('deal_status');
            $table->index('expected_close_date');
            $table->index('company_id');
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};