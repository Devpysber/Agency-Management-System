<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['email', 'call', 'meeting']);
            $table->enum('direction', ['inbound', 'outbound'])->nullable();
            $table->string('subject');
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('completed');
            $table->integer('duration_minutes')->nullable();
            $table->dateTime('occurred_at');

            // Relationships
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('set null');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->foreignId('deal_id')->nullable()->constrained('deals')->onDelete('set null');
            $table->foreignId('staff_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('status');
            $table->index('occurred_at');
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
