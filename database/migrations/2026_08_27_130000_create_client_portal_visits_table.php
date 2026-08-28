<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_portal_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->date('visited_on');
            $table->dateTime('first_seen_at');
            $table->dateTime('last_seen_at');
            $table->unsignedInteger('hits')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'visited_on']);
            $table->index(['company_id', 'visited_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portal_visits');
    }
};
